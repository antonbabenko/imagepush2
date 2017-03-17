<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class FrontController extends Controller
{

    /**
     * @Route("/", name="index")
     * @Template()
     * @Cache(expires="+10 minutes")
     * @Cache(smaxage="3600")
     */
    public function indexAction()
    {
        $images = $this->get('imagepush.repository.image')->findCurrentImages(7);

        return array("images" => array_values($images));
    }

    /**
     * @Route("/upcoming", name="viewUpcoming")
     * @Template()
     */
    public function viewUpcomingAction()
    {
        $response = $this->forward('ImagepushBundle:Front:viewMultiple', array('tag' => null, 'type' => 'upcoming'));

        return $response;
    }

    /**
     * @Route("/tag/{tag}/upcoming", name="viewUpcomingByTag")
     */
    public function viewUpcomingByTagAction($tag)
    {
        $response = $this->forward(
            'ImagepushBundle:Front:viewMultiple',
            array('tag' => urldecode($tag), 'type' => 'upcoming')
        );

        return $response;
    }

    /**
     * @Route("/tag/{tag}", name="viewByTag")
     */
    public function viewByTagAction($tag)
    {
        $response = $this->forward(
            'ImagepushBundle:Front:viewMultiple',
            array('tag' => urldecode($tag), 'type' => 'current')
        );

        return $response;
    }

    /**
     * Universal function to show images by tags/ by type (upcoming/current)
     *
     * @Template()
     * @Cache(expires="+5 minutes")
     * @Cache(smaxage="300")
     */
    public function viewMultipleAction($type, $tag = null)
    {
        $cacheKey = 'view_multiple_' . md5($type.$tag);
        $result = apc_fetch($cacheKey, $inCache);

        if (false !== $inCache) {
            return unserialize($result);
        }

        $isOppositeTypeExists = false;

        if (!is_null($tag)) {
            $tagObject = $this->get('imagepush.repository.tag')->findOneByText($tag);

            if (!$tagObject) {
                throw new NotFoundHttpException(sprintf('There are no %s images to show by tag: %s', $type, $tag));
            }

            // Opposite type field has number of images in each tag, so we can show or hide the opposite type link
            $oppositeTypeField = 'getUsedIn' . ($type !== "current" ? "Available" : "Upcoming");

            $isOppositeTypeExists = (bool) $tagObject->{$oppositeTypeField}();

            $ids = $this->get('imagepush.repository.image')->findImagesIdByTag($tag, 30);

            $images = $this->get('imagepush.repository.image')->findManyByIds($ids, $type == 'current');
        } else {
            $images = $this->get('imagepush.repository.image')->findUpcomingImages(20);
        }

        $result = [
            "type" => $type,
            "tag" => $tag,
            "images" => $images,
            "isOppositeTypeExists" => $isOppositeTypeExists
        ];

        apc_store($cacheKey, serialize($result), 60);

        return $result;
    }

    /**
     * @Route("/p/{id}", requirements={"id"="\d+"}, defaults={"slug"="", "preview"="1"}, name="previewImage")
     * @Route("/i/{id}/{slug}", requirements={"id"="\d+", "slug"=".*"}, defaults={"preview"="0"}, name="viewImage")
     * @Template()
     * @Cache(expires="+1 hour")
     * @Cache(smaxage="86400")
     */
    public function viewImageAction($id, $slug, $preview)
    {
        $cacheKey = 'view_image_' . md5($id.$preview);
        $result = apc_fetch($cacheKey, $inCache);

        if (false !== $inCache) {
            return unserialize($result);
        }

        $repo = $this->get('imagepush.repository.image');

        $image = $repo->findOneBy($id, empty($preview));

        if (!$image) {
            throw new NotFoundHttpException('Image doesn\'t exist');
        }

        $nextImage = $repo
            ->getOneImageRelatedToTimestamp("next", $image->getTimestamp());

        $prevImage = $repo
            ->getOneImageRelatedToTimestamp("prev", $image->getTimestamp());

        $result = ["image" => $image, "nextImage" => $nextImage, "prevImage" => $prevImage];

        apc_store($cacheKey, serialize($result), 3600);

        return $result;

    }

    /**
     * Latest images feeds (rss2.0, rss, atom formats)
     *
     * @Route("/rss2", name="rss2Feed", defaults={"_format"="rss2"})
     * @Route("/rss", name="rssFeed", defaults={"_format"="rss"})
     * @Route("/atom", name="atomFeed", defaults={"_format"="atom"})
     * @Cache(expires="+10 minutes")
     * @Cache(maxage="1800")
     */
    public function latestImagesFeedAction($_format)
    {

        $response = $this->forward(
            'ImagepushBundle:Front:viewMultiple',
            array('type' => 'current', '_format' => $_format)
        );

        if ($_format == "rss2" || $_format == "rss") {
            $response->headers->set('Content-Type', 'application/rss+xml');
        } elseif ($_format == "atom") {
            $response->headers->set('Content-Type', 'application/atom+xml');
        }

        return $response;
    }

    /**
     * Send email to myself, when user mark image as unappropriate or vote up/down.
     *
     * @Route("/flag", name="flagImage", defaults={"type"="flag"})
     * @Route("/vote", name="voteImage", defaults={"type"="vote"})
     */
    public function voteOrFlagImageAction(Request $request, $type)
    {

        $repo = $this->get('imagepush.repository.image');

        $image = $repo->findOneBy((int) $request->get('id'), false);

        if ($image) {

            if ($type == "flag") {
                $subject = 'Image is flagged!';
            } else {
                if (strtolower($request->get('vote')) == "down") {
                    $type = "vote_down";
                    $subject = 'Vote down';
                } else {
                    $type = "vote_up";
                    $subject = 'Vote up';
                }
            }

            $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom(array('noreply@imagepush.to' => "Imagepush votes"))
                ->setTo('anton@imagepush.to')
                ->setBody(
                    $this->renderView(
                        'ImagepushBundle:Emails:voteOrFlagImage.html.twig',
                        array('image' => $image, "type" => $type, "hash" => md5($image->getId()))
                    )
                )
                ->setContentType("text/html");
            $result = $this->get('mailer')->send($message);
        } else {
            $result = "NOT_FOUND";
        }

        return new Response($result);
    }

    /**
     * Display top box with trending tags
     */
    public function _trendingNowAction($max = 20)
    {
        $tags = $this->get('imagepush.repository.latest_tag')
            ->getLatestTrends($max);

        $response = $this->render('ImagepushBundle:Front:_trendingNow.html.twig', ["tags" => $tags]);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    /**
     * Display comment box
     */
    public function _commentsAction($href)
    {
        $response = $this->render('ImagepushBundle:Front:_comments.html.twig', ["href" => $href]);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    /**
     * Display thumb box
     */
    public function _thumbBoxAction($initialTags = array(), $skipImageId = false)
    {
        $cacheKey = 'thumb_box_' . md5(json_encode($initialTags) . json_encode($skipImageId));
        $response = apc_fetch($cacheKey, $inCache);

        if (false !== $inCache) {
            return unserialize($response);
        }

        $imageRepo = $this->get('imagepush.repository.image');
        $latestTagRepo = $this->get('imagepush.repository.latest_tag');

        if (count($initialTags)) {
            $tags = $initialTags;
            $groupTags = false;
            $maxImages = 16;
        } else {
            $tags = $latestTagRepo
                ->getLatestTrends(20);

            if (!count($tags)) {
                return new Response();
            }

            $groupTags = true;
            $maxImages = 4;
        }

        $allImages = $usedImages = [];
        $images = [];
        $totalImages = 0;

        // skip main image
        if (!empty($skipImageId)) {
            $usedImages[] = $skipImageId;
        }

        if ($groupTags) {

            $ids = [];
            $tagsIds = [];

            // @cache-me-please - now APC is doing caching
            foreach ($tags as $tag) {
                // Get some images by tags
                $tmpIds = $imageRepo->findImagesIdByTag($tag, 10);

                if ($tmpIds) {
                    $ids = array_merge($ids, $tmpIds);
                    $tagsIds[$tag] = $tmpIds;
                }
            }

            $ids = array_unique($ids);

            if (count($ids)) {
                $tmpIds = array_chunk($ids, 100);

                foreach ($tmpIds as $tmpId) {
                    $tmpImages = $imageRepo->findManyByIds($tmpId, true, false);
                    if ($tmpImages) {
                        $images = $images + $tmpImages;
                    }
                }
            }

            // Group by tags
            foreach ($tagsIds as $tag => $tmpIds) {

                $tagImages = array();

                // Max 10 groups of images
                if (count($allImages) >= 10) {
                    break;
                }

                // Make sure that each image is shown just once, if image belongs to multiple tags
                foreach ($tmpIds as $id) {

                    if (count($tagImages) >= $maxImages) {
                        break;
                    }

                    if (isset($images[$id]) && !in_array($id, $usedImages)) {
                        $usedImages[] = $id;
                        $image = $images[$id];

                        $tagImages[] = $image;
                    }
                }

                // Skip groups where not enough images
                if (count($tagImages) < $maxImages) {
                    continue;
                }

                $allImages[] = array("tag" => $tag, "images" => $tagImages);
            }
        } else {
            // Do not group images, but order by timestamp
            $tagImages = $foundTags = array();

            $ids = [];
            foreach ($tags as $tag) {
                // Get all images by tags
                $tmpIds = $imageRepo->findImagesIdByTag($tag, 30);

                if ($tmpIds) {
                    $ids = array_merge($ids, $tmpIds);
                }
            }

            $ids = array_unique($ids);

            if (count($ids)) {

                $tmpIds = array_chunk($ids, 100);

                foreach ($tmpIds as $tmpId) {
                    $tmpImages = $imageRepo->findManyByIds($tmpId, true, false);
                    if ($tmpImages) {
                        $images = $images + $tmpImages;
                    }
                }

                // Prepare images for "related images" box, where they all are in one group
                foreach ($ids as $id) {

                    if (count($tagImages) >= $maxImages) {
                        break;
                    }

                    if (isset($images[$id]) && !in_array($id, $usedImages)) {
                        $image = $images[$id];

                        $tagImages[] = $image;

                        $foundTags = array_merge($foundTags, (array) $image->getTags());
                    }
                }
            }

            // Reorder tags
            $foundTags = array_count_values($foundTags);
            arsort($foundTags);
            $foundTags = array_keys(array_slice($foundTags, 0, 5));

            $allImages[] = array("usedTags" => $foundTags, "images" => $tagImages);
            $totalImages += count($tagImages);
        }

        $parameters = [
            "allImages" => $allImages,
            "initialTags" => $initialTags,
            "skipImageId" => $skipImageId,
            "bannerPlacement" => $totalImages > 0 ? mt_rand(0, $totalImages - 1) : 0
        ];

        $response = $this->render('ImagepushBundle:Front:_thumbBox.html.twig', $parameters);
        $response->setSharedMaxAge(86400);

        apc_store($cacheKey, serialize($response), 1800);

        return $response;
    }

    /**
     * Display sidebar box
     */
    public function _sidebarAction()
    {
        $response = $this->render('ImagepushBundle:Front:_sidebar.html.twig');
        $response->setSharedMaxAge(3600);

        return $response;
    }

    /**
     * Display footer
     */
    public function _footerAction()
    {
        $response = $this->render('ImagepushBundle:Front:_footer.html.twig');
        $response->setSharedMaxAge(3600);

        return $response;
    }

    /**
     * @Route("/about", name="about")
     * @Template()
     * @Cache(expires="+1 hour")
     * @Cache(smaxage="86400")
     */
    public function aboutAction()
    {
        return array();
    }

    /**
     * Munin pings this URL to check if site is alive
     *
     * @Route("/status", name="status")
     * @Template()
     */
    public function statusAction()
    {
        return new Response("OK");
    }

}

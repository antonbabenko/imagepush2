<?php

namespace Imagepush\ImagepushBundle\Controller;

use Imagepush\ImagepushBundle\Entity\Image;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FrontController extends Controller
{

    /**
     * @Extra\Route("/", name="index")
     * @Extra\Template()
     * @Extra\Cache(expires="+10 minutes")
     * @Extra\Cache(smaxage="3600")
     */
    public function indexAction()
    {
        $images = $this->get('repository.image')->findImages('current', 7);

        return compact('images');
    }

    /**
     * @Extra\Route("/upcoming", name="viewUpcoming")
     */
    public function viewUpcomingAction()
    {
        return $this->forward('ImagepushBundle:Front:viewMultiple', ['tag' => null, 'type' => 'upcoming']);
    }

    /**
     * @Extra\Route("/tag/{tag}/upcoming", name="viewUpcomingByTag")
     */
    public function viewUpcomingByTagAction($tag)
    {
        return $this->forward('ImagepushBundle:Front:viewMultiple', ['tag' => urldecode($tag), 'type' => 'upcoming']);
    }

    /**
     * @Extra\Route("/tag/{tag}", name="viewByTag")
     */
    public function viewByTagAction($tag)
    {
        return $this->forward('ImagepushBundle:Front:viewMultiple', ['tag' => urldecode($tag), 'type' => 'current']);
    }

    /**
     * Universal function to show images by tags/ by type (upcoming/current)
     *
     * @Extra\Template()
     * @Extra\Cache(expires="+5 minutes")
     * @Extra\Cache(smaxage="300")
     */
    public function viewMultipleAction($type, $tag = null)
    {

        $params = [];

        $isOppositeTypeExists = false;

        if (!is_null($tag)) {
            if (null == $tagObject = $this->get('repository.tag')->findOneByText($tag)) {
                throw new NotFoundHttpException(sprintf('There are no %s images to show by tag: %s', $type, $tag));
            }

            $params = compact('tag');

            // Opposite type field has number of images in each tag, so we can show or hide the opposite type link
            $isOppositeTypeExists = (bool) ('current' !== $type ? $tagObject->getUsedInAvailable() : $tagObject->getUsedInUpcoming());
        }

        $images = $this->get('repository.image')->findImages($type, 30, $params);

        return [
            "type" => $type,
            "tag" => $tag,
            "images" => $images,
            "isOppositeTypeExists" => $isOppositeTypeExists
        ];
    }

    /**
     * @Extra\Route("/p/{id}", requirements={"id"="\d+"}, defaults={"slug"="", "preview"="1"}, name="previewImage")
     * @Extra\Route("/i/{id}/{slug}", requirements={"id"="\d+", "slug"=".*"}, defaults={"preview"="0"}, name="viewImage")
     * @Extra\Template()
     * @Extra\Cache(expires="+1 hour")
     * @Extra\Cache(smaxage="86400")
     */
    public function viewImageAction($id, $slug, $preview)
    {

        if (false == $image = $this->get('repository.image')->findOneImageBy($id, 0 == $preview)) {
            throw new NotFoundHttpException('Image doesn\'t exist');
        }

        $nextImage = $this->get('repository.image')->findOneImageRelatedToObject('next', $image);
        $prevImage = $this->get('repository.image')->findOneImageRelatedToObject('prev', $image);

        return [
            'image' => $image,
            'nextImage' => $nextImage,
            'prevImage' => $prevImage,
        ];
    }

    /**
     * Latest images feeds (rss2.0, rss, atom formats)
     *
     * @Extra\Route("/rss2", name="rss2Feed", defaults={"_format"="rss2"})
     * @Extra\Route("/rss", name="rssFeed", defaults={"_format"="rss"})
     * @Extra\Route("/atom", name="atomFeed", defaults={"_format"="atom"})
     * @Extra\Cache(expires="+10 minutes")
     * @Extra\Cache(maxage="1800")
     */
    public function latestImagesFeedAction($_format)
    {

        $response = $this->forward('ImagepushBundle:Front:viewMultiple', ['type' => 'current', '_format' => $_format]);

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
     * @Extra\Route("/flag", name="flagImage", defaults={"type"="flag"})
     * @Extra\Route("/vote", name="voteImage", defaults={"type"="vote"})
     */
    public function voteOrFlagImageAction(Request $request, $type)
    {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $image = $dm
            ->getRepository('ImagepushBundle:Image')
            ->findOneById((int) $request->get('id'));

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
                ->setFrom(['noreply@imagepush.to' => "Imagepush votes"])
                ->setTo('anton@imagepush.to')
                ->setBody(
                    $this->renderView(
                        'ImagepushBundle:Emails:voteOrFlagImage.html.twig',
                        ['image' => $image, "type" => $type, "hash" => md5($image->getId())]
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
     * @Extra\Route("/about", name="about")
     * @Extra\Template()
     * @Extra\Cache(expires="+1 hour")
     * @Extra\Cache(smaxage="86400")
     */
    public function aboutAction()
    {
        return [];
    }

    /**
     * Munin pings this URL to check if site is alive
     *
     * @Extra\Route("/status", name="status")
     */
    public function statusAction()
    {
        return new Response("OK");
    }

    /**
     * Display top box with trending tags
     *
     * @Extra\Template()
     * @Extra\Cache(smaxage="3600")
     */
    public function _trendingNowAction($limit = 20)
    {
        $tags = $this->get('repository.latest_tag')->getLatestTrends($limit);

        return compact('tags');
    }

    /**
     * Display comment box
     *
     * @Extra\Template()
     * @Extra\Cache(smaxage="86400")
     */
    public function _commentsAction($href)
    {
        return compact('href');
    }

    /**
     * Display thumb box
     *
     * @Extra\Template()
     * @Extra\Cache(smaxage="86400")
     */
    public function _thumbBoxAction($image = null)
    {
        if ($image instanceof Image) {
            $tags = $image->getTagsTextAsArray();
            $usedImages[] = $image->getId();

            $groupTags = false;
            $maxImages = 16;
        } else {
            $tags = $this->get('repository.latest_tag')->getLatestTrends(20);
            $tags = array_column($tags, 'text');

            if (!count($tags) && $image = $this->get('repository.image')->findOneImageRelatedToObject('prev')) {
                $tags = $image->getTagsTextAsArray();
            }

            $usedImages = [];

            $groupTags = true;
            $maxImages = 4;
        }

        $allImages = [];

        // Get all images by tags
        if (count($tags)) {
            $images = $this->get('repository.image')->findImages('current', 10 * count($tags), ['tag' => $tags]);
        } else {
            $images = $this->get('repository.image')->findImages('current', 100);
        }

        if ($groupTags) {
            // Group by tags
            foreach ($tags as $tag) {

                $tagImages = [];

                // Max 10 groups of images
                if (count($allImages) >= 10) {
                    break;
                }

                // Make sure that each image is shown just once, if image belongs to multiple tags
                foreach ($images as $image) {

                    if (count($tagImages) >= $maxImages) {
                        break;
                    }

                    if (!in_array($image->getId(), $usedImages)) {
                        if (in_array($tag, $image->getTagsTextAsArray())) {
                            $tagImages[] = $image;
                            $usedImages[] = $image->getId();
                        }
                    }
                }

                // Skip groups where not enough images
                if (count($tagImages) < $maxImages) {
                    continue;
                }

                $allImages[] = ["tag" => $tag, "images" => $tagImages];
            }
        } else {
            // Do not group images, but order by timestamp
            $tagImages = $foundTags = [];

            // Prepare images for "related images" box, where they all are in one group
            foreach ($images as $image) {

                if (count($tagImages) >= $maxImages) {
                    break;
                }

                if (!in_array($image->getId(), $usedImages)) {
                    $tagImages[] = $image;
                    $usedImages[] = $image->getId();

                    $foundTags = array_merge($foundTags, $image->getTagsTextAsArray());
                }
            }

            // Reorder tags
            $foundTags = array_count_values($foundTags);
            arsort($foundTags);
            $foundTags = array_slice(array_flip($foundTags), 0, 5);

            $allImages[] = ["usedTags" => $foundTags, "images" => $tagImages];
        }

        return [
            'allImages' => $allImages,
            'tags' => $tags,
            'groupTags' => $groupTags,
        ];
    }

    /**
     * Display sidebar box
     *
     * @Extra\Template()
     * @Extra\Cache(smaxage="86400")
     */
    public function _sidebarAction()
    {
        return [];
    }

    /**
     * Display footer
     *
     * @Extra\Template()
     * @Extra\Cache(smaxage="86400")
     */
    public function _footerAction()
    {
        return [];
    }

}

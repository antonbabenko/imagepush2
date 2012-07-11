<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FrontController extends Controller
{

    /**
     * @Route("/", name="index")
     * @Template()
     */
    public function indexAction()
    {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $images = $dm
            ->getRepository('ImagepushBundle:Image')
            ->findImages("current", 7);

        /* $i = array_values($images);
          $image = $i[0];
          echo "<br>==".$mainImage = $this->get('twig.extension.imagepush')->imagepushFilter($image->getFile(), 'in', 463, 1548, $image->getId());
          echo "<br>==".$mainImageWidth = $image->getThumbProperty("in", 463, 1548, "w");
          echo "<br>==".$mainImageHeight = $image->getThumbProperty("in", 463, 1548, "h");
         */
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
     * @Template()
     */
    public function viewUpcomingByTagAction($tag)
    {
        $response = $this->forward('ImagepushBundle:Front:viewMultiple', array('tag' => $tag, 'type' => 'upcoming'));

        return $response;
    }

    /**
     * @Route("/tag/{tag}", name="viewByTag")
     * @Template()
     */
    public function viewByTagAction($tag)
    {
        $response = $this->forward('ImagepushBundle:Front:viewMultiple', array('tag' => $tag, 'type' => 'current'));

        return $response;
    }

    /**
     * Universal function to show images by tags/ by type (upcoming/current)
     * @Template()
     */
    public function viewMultipleAction($type, $tag = null)
    {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $params = array();

        $isOppositeTypeExists = false;

        if (!is_null($tag)) {
            $params = array("tag" => $tag);

            $tagObject = $dm->createQueryBuilder('ImagepushBundle:Tag')
                ->field('text')->equals($tag)
                ->getQuery()
                ->getSingleResult();

            if (!$tagObject) {
                throw new NotFoundHttpException(sprintf('There are no %s images to show by tag: %s', $type, $tag));
            }

            //$typeField = 'getUsedIn' . ($type == "current" ? "Available" : "Upcoming");
            // && $tagObject->{$typeField}() > 0))
            // Opposite type field has number of images in each tag, so we can show or hide the opposite type link
            $oppositeTypeField = 'getUsedIn' . ($type !== "current" ? "Available" : "Upcoming");

            $isOppositeTypeExists = (bool) $tagObject->{$oppositeTypeField}();
        }

        $images = $dm
            ->getRepository('ImagepushBundle:Image')
            ->findImages($type, 30, $params);

        return array(
            "type" => $type,
            "tag" => $tag,
            "images" => $images,
            "isOppositeTypeExists" => $isOppositeTypeExists
        );
    }

    /**
     * @Route("/i/{id}/{slug}", requirements={"id"="\d+", "slug"=".*"}, name="viewImage")
     * @Template()
     */
    public function viewImageAction($id)
    {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $image = $dm
            ->getRepository('ImagepushBundle:Image')
            ->findOneBy(array("id" => (int) $id, "isAvailable" => true));

        //\D::dump($id);
        //\D::dump($image->getMimeType());

        if (!$image) {
            throw new NotFoundHttpException('Image doesn\'t exist');
        }

        $nextImage = $dm
            ->getRepository('ImagepushBundle:Image')
            ->getOneImageRelatedToTimestamp("next", $image->getTimestamp());

        $prevImage = $dm
            ->getRepository('ImagepushBundle:Image')
            ->getOneImageRelatedToTimestamp("prev", $image->getTimestamp());

        return array("image" => $image, "nextImage" => $nextImage, "prevImage" => $prevImage);
    }

    /**
     * Latest images feeds (rss2.0, rss, atom formats)
     *
     * @Route("/rss2", name="rss2Feed", defaults={"_format"="rss2"})
     * @Route("/rss", name="rssFeed", defaults={"_format"="rss"})
     * @Route("/atom", name="atomFeed", defaults={"_format"="atom"})
     */
    public function latestImagesFeedAction($_format)
    {

        $response = $this->forward('ImagepushBundle:Front:viewMultiple', array('type' => 'current', '_format' => $_format));

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

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $image = $dm
            ->getRepository('ImagepushBundle:Image')
            ->findOneById((int) $request->request->get('id'));

        if ($image) {

            if ($type == "flag") {
                $subject = 'Image is flagged!';
            } else {
                if (strtolower($request->request->get('vote')) == "down") {
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
                ->setBody($this->renderView('ImagepushBundle:Emails:voteOrFlagImage.html.twig', array('image' => $image, "type" => $type)))
                ->setContentType("text/html");
            $result = $this->get('mailer')->send($message);
        } else {
            $result = "NOT_FOUND";
        }

        return new Response($result);
    }

    /**
     * Display top box with trending tags
     * 
     * @Template()
     */
    public function _trendingNowAction($max = 20)
    {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $tags = $dm
            ->getRepository('ImagepushBundle:LatestTag')
            ->getLatestTrends($max);

        return array("tags" => $tags);
    }

    /**
     * Display comment box
     * 
     * @Template()
     */
    public function _commentsAction($href)
    {
        return array("href" => $href);
    }

    /**
     * Display thumb box
     * 
     * @Template()
     */
    public function _thumbBoxAction($initialTags = array(), $skipImageId = false, $withAd = false)
    {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        //\D::dump($initialTags);
        if (count($initialTags)) {
            $tags = $initialTags;
            $groupTags = false;
            $maxImages = 16;
        } else {
            $tags = $dm
                ->getRepository('ImagepushBundle:LatestTag')
                ->getLatestTrends(100);
            //\D::dump($tags);

            if (!count($tags)) {
                return;
            }

            $tags = array_keys($tags);

            $groupTags = true;
            $maxImages = 4;
        }

        //$tagImages = 
        $allImages = $usedImages = array();
        $totalImages = 0;

        // skip main image
        if (!empty($skipImageId)) {
            $usedImages[] = $skipImageId;
        }

        //\D::dump($tags);
        // Get all images by tags
        $images = $dm
            ->getRepository('ImagepushBundle:Image')
            ->findImages("current", 10 * count($tags), array("tag" => $tags));
        $images = array_values($images);

        //\D::dump($images);

        if ($groupTags) {
            // Group by tags
            foreach ($tags as $tag) {

                $tagImages = array();

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
                        if (in_array($tag, $image->getTags())) {
                            $tagImages[] = $image;
                            $usedImages[] = $image->getId();
                        }
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

            // Prepare images for "related images" box, where they all are in one group
            foreach ($images as $image) {

                if (count($tagImages) >= $maxImages) {
                    break;
                }

                if (!in_array($image->getId(), $usedImages)) {
                    $tagImages[] = $image;
                    $usedImages[] = $image->getId();

                    $foundTags = array_merge($foundTags, (array) $image->getTags());
                }
            }

            // Reorder tags
            $foundTags = array_count_values($foundTags);
            arsort($foundTags);
            $foundTags = array_slice(array_flip($foundTags), 0, 5);

            $allImages[] = array("usedTags" => $foundTags, "images" => $tagImages);
            $totalImages += count($tagImages);
            //\D::dump($allImages);
        }

        //\D::dump($allImages);
        //\D::dump($withAd);
        //\D::dump($initialTags);

        return array(
            "allImages" => $allImages,
            "initialTags" => $initialTags,
            "skipImageId" => $skipImageId,
            "withAd" => $withAd,
            "bannerPlacement" => $totalImages > 0 ? mt_rand(0, $totalImages - 1) : 0);
    }

    /**
     * @Route("/about", name="about")
     * @Template()
     */
    public function aboutAction()
    {
        return array();
    }

}

<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class RobotController extends Controller
{

    /**
     * @Route("/rabbitmq", name="rabbit")
     * @Template()
     */
    public function rabbitPublishAction()
    {

        $time = microtime(true);

        $msg = array("image_id" => 43516, "task" => \Imagepush\ImagepushBundle\Consumer\MessageTask::FIND_TAGS_AND_MENTIONS);
        //$this->get('old_sound_rabbit_mq.reddit_producer')->publish(json_encode($msg));
        $this->get('old_sound_rabbit_mq.primary_producer')->publish(json_encode($msg));
        $this->get('old_sound_rabbit_mq.primary_producer')->publish(json_encode($msg));
        $this->get('old_sound_rabbit_mq.primary_producer')->publish(json_encode($msg));
        //sleep(2);
        //$msg = array("image_id" => 43517, "task" => \Imagepush\ImagepushBundle\Consumer\MessageTask::FIND_TAGS_AND_MENTIONS);
        /*$this->get('old_sound_rabbit_mq.twitter_producer')->publish(json_encode($msg));
        $this->get('old_sound_rabbit_mq.twitter_producer')->publish(json_encode($msg));
        $this->get('old_sound_rabbit_mq.twitter_producer')->publish(json_encode($msg));
        $this->get('old_sound_rabbit_mq.twitter_producer')->publish(json_encode($msg));
        $this->get('old_sound_rabbit_mq.twitter_producer')->publish(json_encode($msg));
        $this->get('old_sound_rabbit_mq.twitter_producer')->publish(json_encode($msg));
         *
         */
        //$msg = array("image_id" => 43518, "task" => \Imagepush\ImagepushBundle\Consumer\MessageTask::FIND_TAGS_AND_MENTIONS);
        //$producer->publish(json_encode($msg));

        /* $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'request_id');
          $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'request_id');
          $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'request_id');
          $replies = $client->getReplies();

          /*
          $client = $this->get('old_sound_rabbit_mq.parallel_rpc');
          $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'request_id1');
          $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'request_id2');
          $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'request_id3');
          $replies = $client->getReplies();
         */

        echo sprintf("%f", microtime(true) - $time);

        //\D::dump($replies);
        return new Response();
    }

    /**
     * @Route("/robot/{action}", name="robot")
     * @Template()
     */
    public function indexAction($action)
    {
        die();

        //return new Response("Use CLI commands instead of this");

        $content = "";

        switch ($action) {
            case "fetchFromDigg":
                $content = $this->get('imagepush.fetcher.digg')->run();
                break;

            /**
             * Process source = get source, find images, make thumbs, find related tags
             */
            case "processSource":
                $content = $this->get('imagepush.processor')->processSource();
                break;

            // Use only for tests:
            case "processTags":
                $dm = $this->get('doctrine.odm.mongodb.document_manager');

                $image = $dm
                    ->getRepository('ImagepushBundle:Image')
                    ->findOneBy(array("id" => 100015));
                $content = $this->get('imagepush.processor.tag')->processTags($image);
                break;

            /**
             * Publish upcoming images
             */
            case "publishLatestUpcomingImage":
                $content = $this->get('imagepush.publisher')->publishLatestUpcomingImage();
                break;

            /**
             * Just for test
             */
            case "testMakeThumbs":
                define("AWS_CERTIFICATE_AUTHORITY", true);
                //$fs = $this->get('knp_gaufrette.filesystem_map')->get('images');

                $fs = $this->get('knp_gaufrette.filesystem_map')->get('images');
                //\D::dump($fs->keys());
                //$fs->write('s3.txt', 'some content');

                $content = $fs->has('s3.txt');
                \D::dump($content);
                \D::dump($fs);
                $fileContent = file_get_contents("http://dev-anton.imagepush.to/test_images/1.jpg");
                $fileContentType = "image/jpeg";
                //$content = $this->get('imagepush.processor.image')->testMakeThumbs($fileContent, $fileContentType);
                break;

            default:
                $content = sprintf("Error: Action '%s' is not implemented yet.", $action);
                break;
        }

        return array("content" => (array) $content);
    }

}

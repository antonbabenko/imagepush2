<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class RobotController extends Controller
{

  /**
   * @Route("/{action}", name="robot")
   * @Template()
   */
  public function indexAction($action)
  {
    
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
    
    return array("content" => is_array($content) ? $content : (array)$content);
    
  }


}

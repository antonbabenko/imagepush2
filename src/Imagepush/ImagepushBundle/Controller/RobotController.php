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
    
//    $images = $this->get('imagepush.images')->getImages("current", 7);
    $content = "";
    
    switch ($action) {
      case "fetchFromDigg":
        $content = $this->get('imagepush.fetcher.digg')->run();
        break;
      
      /**
       * Process source = get html pages, find images
       */
      case "processSource":
        $content = $this->get('imagepush.process.source')->run();
        break;
      
      default:
        $content = sprintf("Error: Action '%s' is not implemented yet.", $action);
        break;

    }
    
    return array("content" => is_array($content) ? $content : (array)$content);
    
  }


}

<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Processor;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Imagepush\ImagepushBundle\Document\Image;

class ProcessorTest extends WebTestCase
{
    /* public function setup()
      {
      parent::setup();

      // Find the way to mock mongodb document manager!!!
      //$this->getMockDocumentManager();
      //$this->populate();
      } */

    /*
      public function testProcessSource()
      {
      $client = self::createClient();
      //$service = $client->getContainer()->get('imagepush.processor');
      //$result = $service->processSource();
      //\D::debug($result);

      $dm = $client->getContainer()->get('doctrine.odm.mongodb.document_manager');
      $hash = "a8097ad76766456a21273292d4981238";

      $result = $dm->getRepository('ImagepushBundle:ProcessedHash')->findOneBy(array("hash" => $hash));
      \D::debug($result);
      }
     */

    /*
      private function populate()
      {

      $image1 = new Image();
      $image1->setId(1);
      $image1->setLink('http://www.google.com/page1');
      $image1->setIsAvailable(false);
      $image1->setIsInProcess(false);
      $this->dm->persist($image1);

      $image2 = new Image();
      $image2->setId(2);
      $image2->setLink('http://www.google.com/page2');
      $image2->setIsAvailable(false);
      $image2->setIsInProcess(false);
      $this->dm->persist($image2);

      $this->dm->flush();
      $this->dm->clear();
      }
     */
}

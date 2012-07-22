<?php

namespace Imagepush\ImagepushBundle\Tests\Document;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImageTest extends WebTestCase
{

    /**
     * Part of Document\Image::updateFilename(), which generates filename based on id
     * 
     * @param integer $id
     */
    private function generateFilename($id, $fileExt = "jpg")
    {
        $file = floor($id / 10000) . "/";
        $file .= floor($id / 1000) . "/";
        $file .= floor($id / 100) . "/";
        $file .= ( $id % 100) . "." . $fileExt;

        return $file;
    }

    public function testFilenames()
    {
        $this->assertEquals("0/0/0/1.jpg", $this->generateFilename(1));
        $this->assertEquals("0/0/1/23.jpg", $this->generateFilename(123));
        $this->assertEquals("0/1/12/30.jpg", $this->generateFilename(1230));
        $this->assertEquals("1/12/123/0.jpg", $this->generateFilename(12300));
        $this->assertEquals("1/12/120/0.jpg", $this->generateFilename(12000));
        $this->assertEquals("1/10/100/0.jpg", $this->generateFilename(10000));
        $this->assertEquals("10/100/1000/0.jpg", $this->generateFilename(100000));
        $this->assertEquals("0/1/10/0.jpg", $this->generateFilename(1000));
    }

}

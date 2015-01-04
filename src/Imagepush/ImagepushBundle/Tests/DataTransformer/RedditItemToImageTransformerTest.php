<?php

namespace Imagepush\ImagepushBundle\Tests\DataTransformer;

use Imagepush\DevBundle\Test\Phpunit\WebTestCase;
use Imagepush\ImagepushBundle\DataTransformer\RedditItemToImageTransformer;

class RedditItemToImageTransformerTest extends WebTestCase
{

    public function testConstruct()
    {
        $this->assertInstanceOf('Imagepush\ImagepushBundle\DataTransformer\RedditItemToImageTransformer', $this->getDataTransformerService());
    }

    public function testShouldSetGetItem()
    {
        $item = ['id' => '123', 'title' => 'title'];
        $this->getDataTransformerService()->setItem($item);

        $this->assertEquals($item, $this->getDataTransformerService()->getItem());
    }

    public function testShouldValidateItem()
    {
        $item = ['id' => '123', 'title' => 'title'];
        $this->getDataTransformerService()->setItem($item);

        $errors = $this->getDataTransformerService()->validate();

        $this->assertEquals(1, count($errors));
        var_dump($errors);

    }

    /**
     * @return RedditItemToImageTransformer
     */
    protected function getDataTransformerService()
    {
        return static::$container->get('data_transformer.reddit_to_image');
    }
}

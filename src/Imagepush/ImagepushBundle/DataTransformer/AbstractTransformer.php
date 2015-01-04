<?php

namespace Imagepush\ImagepushBundle\DataTransformer;

use Imagepush\ImagepushBundle\Determiner\DeterminerInterface;
use Imagepush\ImagepushBundle\Entity\Image;

abstract class AbstractTransformer
{
    /**
     * @var mixed
     */
    protected $items;

    /**
     * @var array
     */
    protected $objects;

    /**
     * @var boolean
     */
    protected $isTransformed;

    /**
     * @var DeterminerInterface
     */
    protected $determiner;

    public function __construct()
    {
        $this->items = null;
        $this->objects = [];
        $this->isTransformed = false;
    }

    /**
     * @param mixed $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function getObjects()
    {
        if (false == $this->isTransformed) {
            $this->transform();
        }

        return $this->objects;
    }

    /**
     * @return void
     */
    protected function transform()
    {

        foreach ($this->items as $item) {
            if ($this->getDeterminer()->isWorthToSave($item)) {
                $this->objects[] = $this->transformItemIntoObject($item);
            }
        }

        $this->isTransformed = true;
    }

    /**
     * @return DeterminerInterface
     */
    public function getDeterminer()
    {
        return $this->determiner;
    }

    /**
     * @param DeterminerInterface $determiner
     */
    public function setDeterminer(DeterminerInterface $determiner)
    {
        $this->determiner = $determiner;
    }

    /**
     * @param $item
     *
     * @return Image
     *
     * @throws \LogicException
     */
    public function transformItemIntoObject($item)
    {
        throw new \LogicException('This method must be overridden to do item transformation into Entity\Image');
    }

}

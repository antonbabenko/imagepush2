<?php

namespace Imagepush\ImagepushBundle\Document;

/**
 * Tag
 */
class Tag
{

    /**
     * @var string
     */
    protected $text;

    /**
     * @var integer
     */
    protected $usedInAvailable;

    /**
     * @var integer
     */
    protected $usedInUpcoming;

    /**
     * @param array $data
     */
    public function fromArray(array $data)
    {
        $this->setText(array_values($data['text'])[0]);

        if (isset($data['usedInAvailable'])) {
            $this->setUsedInAvailable(
                array_values($data['usedInAvailable'])[0]
            );
        } else {
            $this->setUsedInAvailable(0);
        }

        if (isset($data['usedInUpcoming'])) {
            $this->setUsedInUpcoming(
                array_values($data['usedInUpcoming'])[0]
            );
        } else {
            $this->setUsedInUpcoming(0);
        }

    }

    /**
     * @return array
     */
    public function toItem()
    {
        $item = [
            'text' => [
                'S' => strval($this->getText())
            ],
            'usedInAvailable' => [
                'N' => strval((int) $this->getUsedInAvailable())
            ],
            'usedInUpcoming' => [
                'N' => strval((int) $this->getUsedInUpcoming())
            ],
        ];

        return $item;
    }

    /**
     * Set text
     *
     * @param string
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set usedInAvailable
     *
     * @param integer
     */
    public function setUsedInAvailable($usedInAvailable)
    {
        $this->usedInAvailable = $usedInAvailable;
    }

    /**
     * Get usedInAvailable
     *
     * @return integer
     */
    public function getUsedInAvailable()
    {
        return $this->usedInAvailable;
    }

    /**
     * Increment usedInAvailable
     *
     * @param integer
     */
    public function incUsedInAvailable($inc = 1)
    {
        $this->usedInAvailable += $inc;
    }

    /**
     * Set usedInUpcoming
     *
     * @param integer
     */
    public function setUsedInUpcoming($usedInUpcoming)
    {
        $this->usedInUpcoming = $usedInUpcoming;
    }

    /**
     * Get usedInUpcoming
     *
     * @return integer
     */
    public function getUsedInUpcoming()
    {
        return $this->usedInUpcoming;
    }

    /**
     * Increment usedInUpcoming
     *
     * @param integer
     */
    public function incUsedInUpcoming($inc = 1)
    {
        $this->usedInUpcoming += $inc;
    }

}

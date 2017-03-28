<?php

namespace Imagepush\ImagepushBundle\Document;

/**
 * Latest tags to use when show latest trend
 */
class LatestTag
{

    /**
     * @var string
     */
    protected $id;

    /**
     * @var integer
     */
    protected $timestamp;

    /**
     * Timeslot is number of hours since epoch (intdiv(timestamp, 3600))
     *
     * @var integer
     */
    protected $timeslot;

    /**
     * @var string
     */
    protected $text;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return integer
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param integer
     */
    public function setTimeslot($timeslot)
    {
        $this->timeslot = $timeslot;
    }

    /**
     * @return integer
     */
    public function getTimeslot()
    {
        return $this->timeslot;
    }

    /**
     * @param string
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return array
     */
    public function toItem()
    {
        $item = [
            'id' => [
                'B' => base64_encode(microtime() . $this->getText())
            ],
            'timestamp' => [
                'N' => strval($this->getTimestamp())
            ],
            'timeslot' => [
                'N' => strval(intdiv($this->getTimestamp(), 3600))
            ],
            'text' => [
                'S' => strval($this->getText())
            ],
        ];

        return $item;
    }

}

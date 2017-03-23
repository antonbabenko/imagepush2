<?php

namespace Imagepush\ImagepushBundle\Document;

/**
 * Original links (indexed, failed, blocked)
 */
class Link
{

    const FAILED = "failed";
    const INDEXED = "indexed";
    const BLOCKED = "blocked";

    /**
     * @var string
     */
    protected $link;

    /**
     * Link status - indexed, failed, blocked
     *
     * @var string
     */
    protected $status;

    /**
     * @param string $link   Link
     * @param string $status Status
     */
    public function __construct($link = null, $status = null)
    {
        $this->setLink($link);
        $this->setStatus($status);
    }

    /**
     * Set link
     *
     * @param string
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set status
     *
     * @param string
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function toItem()
    {

        $item = [
            'link' => [
                'S' => strval($this->getLink())
            ],
            'status' => [
                'S' => strval($this->getStatus())
            ],
        ];

        return $item;

    }

}

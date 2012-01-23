<?php

namespace Imagepush\ImagepushBundle\Document;

use Nekland\FeedBundle\Item\ExtendedItemInterface;

class ImageFeedItem extends Image implements ExtendedItemInterface
{
  
  public function __construct(Image $image) {
    $this->image = $image;
  }
  
  /*
   * Return the title of your rss, something like "My blog rss"
   * @return string
   */

  public function getFeedTitle()
  {
    return $this->image->title;
  }

  /*
   * Return the description of your rss, someting like "This is the rss of my blog about foo and bar"
   * @return string
   */

  public function getFeedDescription()
  {
    
  }

  /*
   * Return the route of your item
   * @return array with 
   * [0]
   *      =>
   *      	['route']
   *      			=>
   *          			[0] =>  'route_name'
   *          			[1] =>  array of params of the route
   *     	=>
   *      	['other parameter'] => 'content' (you can use for atom)
   * [1]
   *     	=>
   *     		['url'] => 'http://mywebsite.com'
   *     	=>
   *      	['other parameter'] => 'content' (you can use for atom)
   */

  public function getFeedRoutes()
  {
    return array(
      array("route" => array("viewImage", array("id" => $this->image->id, "slug" => $this->image->slug))),
      array("url" => "http://imagepush.to/")
    );
  }

  /**
   * @return unique identifiant (for editing)
   */
  public function getFeedId()
  {
    return $this->image->id;
  }

  /**
   * @abstract
   * @return \DateTime
   */
  public function getFeedDate()
  {
    return new \DateTime("@" . $this->image->timestamp->sec);
  }

  /**
   * Somethink like array('name' => 'foo', 'email' => 'foo@bar.com', 'website' => 'http://foo.bar.com')
   * 
   * @return array
   */
  public function getFeedAuthor() {}

  /**
   * @return string
   */
  public function getFeedCategory() {}

  /**
   * @return string|array with [0] => 'route_name', [1] => params
   */
  public function getFeedCommentRoute() {}

  /**
   * 
   * @return array with param1 => "value1", param3 => "value 2", ...
   */
  public function getFeedEnclosure() {
    return array("image1" => "file1.jpg");
  }

  /**
   * @return string
   */
  public function getFeedSummary() {}
}

<?php

namespace Imagepush\ImagepushBundle\Services\Processors;

/**
 * @todo: Other classes are:
 *   Processors\processor - super-class which handles all other processors relations and contains business logic
 *   Processors\content - get image by the source link, which is the most suitable
 *   Processors\tags - get tags for the link
 *   Processors\images - make thumbs, save images
 */

/*
class ContentBrowser extends sfWebBrowser
{

  public function __construct()
  {
    parent::__construct(array(), null, array("ssl_verify" => false, "timeout" => 20));
  }

}
*/

class Processor
{

  public $source;
  
  public $id;
  public $link;
  public $imageKey;
  
  /*
   * @services
   */
  public $kernel;
  
  public function __construct(\AppKernel $kernel) {
    
    $this->kernel = $kernel;
    
  }
  
  //////////////////
  static $allowedImageContentTypes = array("image/gif", "image/jpeg", "image/jpg", "image/png");
  static $result;

  /*
   * If thumbs couldn't be saved or image is small - then remove Image key object
   */
  public $removeKeyIfImageIsSmallOrError = false;

  /*
   * ContentBrowser
   */
  //protected $b;

  /*
   * @todo if porn/nudes domain - return true
   */
  public function isBlockedDomain()
  {
    return false;
  }
  

  public function contentIsImage() {
    
    return (!empty($this->data["Content-type"]) && in_array($this->data["Content-type"], self::$allowedImageContentTypes));
    
  }

  public function contentIsXMLLike() {
    
    return (!empty($this->data["Content-type"]) && (preg_match('/(x|ht)ml/i', $this->data["Content-type"])));
    
  }
  
  public function isAlreadyProcessedImageHash($hashValue) {
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    return $redis->sismember('processed_image_hash', $hashValue);
    
  }
  
  public function saveProcessedImageHash($hashValue) {
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    return $redis->sadd('processed_image_hash', $hashValue);
    
  }
  
  public function run()
  {

    // 1) get one latest urls, which is unprocessed and not blocked by other working process
    // 2) check what kind of site is it - blocked (nudes, porn) or good
    // 3) get content from URL
    // 4) check type of content - image or html
    // 4a) if single image -> then it is "unsorted" category
    // 4b) if html:
    // 5) try to find large image(s)
    // 6) try to make thumbs from large image
    // 7) try to find category/tags for the page

    $source = $this->kernel->getContainer()->get('imagepush.source');
    $images = $this->kernel->getContainer()->get('imagepush.images');
    $processorContent = $this->kernel->getContainer()->get('imagepush.processor.content');
    $processorImage = $this->kernel->getContainer()->get('imagepush.processor.image');
    
    $this->source = $source->getAndInitUnprocessed();
    
    if ($this->source === false) {
      throw new \Exception("There is no unprocessed source to work on");
    }
    
    $this->id = $this->source["id"];
    $this->link = $this->source["link"];
    //$this->link = 'http://i.imgur.com/QGdKg.jpg';
    $this->imageKey = $images->getImageKey($this->id);

    \D::dump($this->source);
    
    if ($this->isBlockedDomain()) {
      throw new \Exception(sprintf("%s is blacklisted domain (porn, spam, etc)", $this->link));
    }
    
    // nothing to do if no link or link is blocked
    //if (!$this->link || self::isBlockedDomain($this->link))
      //return false;
    
    /*
      $this->link = "http://www.web-developer.no/img/portfolio/flynytt.jpg";
      $this->link = "http://dev.local.imagepush.to/test-images/test1.jpg";
      $this->link = "http://i.imgur.com/bCK24.jpg";
      $this->link = "http://adayinthalifeof.files.wordpress.com/2009/06/picture-15.png";
      $this->link = "http://115.124.110.108/forum/uploads/monthly_09_2009/post-2576-1252011550.png";

      // text/html
      $this->link = "http://www.web-developer.no/";
      $this->link = "http://slapblog.com/?p=7888";
      $this->link = "http://localhost/testpages/page1.htm";
      //$this->link = "http://localhost/testpages/page2.htm"; // image_src
      //$this->link = "http://localhost/testpages/page3.html";
     */

    // ssl problem:
    // $this->link = "http://delaware.metromix.com/music/essay_photo_gallery/most-anticipated-albums-of/2389330/photo/2393050";
    // parse url problem
    // $this->link = "http://www.totalprosports.com/2011/01/19/is-that-a-rocket-in-caroline-wozniackis-pocket-pic/";

    $this->data = $processorContent->get($this->link);
    
    if (!is_array($this->data)) {
      $this->kernel->getContainer()->get('logger')->warn(sprintf("ID: %d. Link %s returned status code %d", $this->id, $this->link, $this->data));
      return false;
    }
    
    if ($this->contentIsImage()) {
      
      if ($this->isAlreadyProcessedImageHash($this->data["Content-md5"])) {
        $this->kernel->getContainer()->get('logger')->warn(sprintf("ID: %d. Image %s has been already processed (hash found)", $this->id, $this->link));
        return false;
      }
      
      $thumbs = $processorImage->makeThumbs($this->id, $this->data);
      if ($thumbs !== false) {
        // comment these lines if testing
        //$this->saveProcessedImageHash($this->data["Content-md5"]);
        //$images->saveAsProcessed($this->imageKey, array_merge($this->source, $thumbs));
      }
    
    } elseif ($this->contentIsXMLLike()) {
      
      /**
       * Try to find <link rel="image_src" />
       */
      $imageSrcUri = $processorContent->getFullImageSrc($this->data["Content"]);
      
      if ($imageSrcUri !== false) {
        $imageSrcContent = $processorContent->get($imageSrcUri);
        
        if ($imageSrcContent && $this->contentIsImage($imageSrcContent)) {
          $result = $processorImage->makeThumbs($imageSrcContent);
          
          if ($result) break;
        }
      }
      
      /*
       * Large images inside h1, h2, h3, div
       */
      $result = $processorContent->getBestImageFromDom($this->data["Content"]);

      if (!$result)
      {
        $images->removeKey($this->key, $this->link);
      }
      
    }
    
    return (isset($result) && $result ? $this->key : false);
    
    /*
    try {
      $this->b = new ContentBrowser();
      if (!$this->b->get($this->link)->responseIsError())
      {

        // Successful response (eg. 200, 201, etc)
        $content_type = $this->b->getResponseHeader("Content-Type");
        if (in_array($content_type, self::$allowed_image_types))
        {

          $this->removeKeyIfImageIsSmallOrError = true;
          $result = $this->processAsSingleImage();
        } elseif (preg_match('/(x|ht)ml/i', $content_type))
        {
          // Try to guess for main image
          $dom = $this->b->getResponseDom();
          $dom->preserveWhiteSpace = false;

          //Images::saveCachedDom($this->key, serialize($dom));

          // <link rel="image_src" />
          // Most-likely image_url is not helpful, because it is tiny
           
          if (false != $image_src = $this->getFullImageSrcFromDom($dom))
          {
            $b_image_src = new ContentBrowser();
            if (!$b_image_src->get($image_src)->responseIsError() &&
              in_array($b_image_src->getResponseHeader("Content-Type"), self::$allowed_image_types))
            {
              $this->removeKeyIfImageIsSmallOrError = false;
              $result = $this->processAsSingleImage($b_image_src);

              if ($result)
                break;
            }
          }

          // Large images inside h1, h2, h3, div
          $result = $this->getBestImageFromDom($dom);

          if (!$result)
          {
            Images::removeKey($this->key, $this->link);
          }

          D::dump($result);
          //D::dump_dom($dom);
        } else
        {
          Images::removeKey($this->key, $this->link);
          sfContext::getInstance()->getLogger()->warning($this->link . " - Content type " . $content_type . " is not supported.");
        }
      } else
      {
        // Error response (eg. 404, 500, etc)
        // todo: try to fetch this content in some time, probably
        sfContext::getInstance()->getLogger()->err($this->link . " - Code: " . $this->b->getResponseCode() . "; Message: " . $this->b->getResponseMessage());

        Images::removeKey($this->key, $this->link);
      }
    } catch (Exception $e) {
      // Adapter error (eg. Host not found)
      sfContext::getInstance()->getLogger()->err($e->getMessage());

      Images::removeKey($this->key, $this->link);
    }

    return (isset($result) && $result ? $this->key : false);
    */
    
  }

  /*
   * Handles all manipulation for one object (check size, resize thumbs, save Image object)
   */

  protected function processAsSingleImage(ContentBrowser $b = null)
  {

    if (empty($b))
    {
      $b = $this->b;
    }

    $saved = false;

    $content = $b->getResponseText();
    $content_type = $b->getResponseHeader("Content-Type");

    // if the same image has been already processed (but from another place), then remove it now
    if (Images::isUniqueImageHash($content)) {
      Images::removeKey($this->key, $this->link);

      $message = "Link: " . $this->link . " - Such image hash has been already processed.";
      sfContext::getInstance()->getLogger()->info($message);

      return $saved;
    }

    $image = new ImageManipulation();
    $image->setImageAsString($content, $content_type);
    $image->setImageId($this->id);

    if ($image->getImage()->getWidth() >= self::$min_width && $image->getImage()->getHeight() >= self::$min_height)
    {

      $message = "Link: " . $this->link . " (" . $image->getImage()->getWidth() . "x" . $image->getImage()->getHeight() . ") - Make thumbs";
      sfContext::getInstance()->getLogger()->info($message);

      $thumbs_data = $image->storeThumbs();
      //D::dump($thumbs_data);

      if ($thumbs_data)
      {
        Images::saveUniqueImageHash($content);
        Images::saveAsProcessed($this->key, array_merge($this->working_link, $thumbs_data));
        $saved = true;
      } else
      {
        if ($this->removeKeyIfImageIsSmallOrError)
        {
          Images::removeKey($this->key, $this->link);
        }

        $message = "Link: " . $this->link . " - Didn't make thumbs, so link will be removed (key: " . $this->key . ")";
        sfContext::getInstance()->getLogger()->info($message);
      }
    } else
    { // very small image
      if ($this->removeKeyIfImageIsSmallOrError)
      {
        Images::removeKey($this->key, $this->link);
      }

      $message = "Link: " . $this->link . " (" . $image->getImage()->getWidth() . "x" . $image->getImage()->getHeight() . ") - Small image, remove link";
      sfContext::getInstance()->getLogger()->info($message);
    }

    return $saved;
  }

  public function getBestImageFromDom($dom)
  {

    /*
     * Priority for the best image on the page:
     * 1) get all img inside body
     * 2) keep images, which have aspect ratio between 0.3 and 2.5 AND width >= 450
     * 3) get xpath for selected large images
     * 4) find tags inside content area by patterns ("labels:", "keywords:", ...)
     *
     */

    $dom_img = $dom->documentElement->getElementsByTagName("img");
    //D::dump_dom($dom_img);

    $total_images = $dom_img->length;
    //D::dump($total_images);

    if (!$total_images)
      return false;

    $fetch_images = array();
    $result = false;

    // go through images
    for ($i = 0; $i < $total_images; $i++) {

      $src = $dom_img->item($i)->getAttribute("src");

      if (empty($src) || preg_match("/data\:image\//", $src))
      {
        continue;
      }

      if (preg_match("/^http(.+)/i", $src))
      {
        $img_src = $src;
      } else
      {

        if (preg_match("/^\/(.+)/", $src) && $_url = parse_url($this->link))
        { // path from site root
          $img_src = $_url["scheme"] . "://" . $_url["host"] . $src;
        } else
        { // relative url
          $img_src = dirname($this->link) . "/" . $src;
        }
      }
      //D::dump($img_src);

      $w = ($dom_img->item($i)->getAttribute("width") ? : 0);
      $h = ($dom_img->item($i)->getAttribute("height") ? : 0);

      $r = ($w && $h && $h != 0 ? round($w / $h) : 0);


      // check ratio, width, height
      if ($r && $r >= self::$min_ratio && $r <= self::$max_ratio && $w >= self::$min_width && $h >= self::$min_height)
      {
        $fetch_images[] = array("src" => $img_src, "xpath" => $dom_img->item($i)->getNodePath());
        continue;
      }

      // do HEAD request and check min filesize and content-type
      if (!$w || !$h)
      { // do HEAD request to
        $img_browser = new ContentBrowser();
        $img_browser->head($img_src);

        $content_type = $img_browser->getResponseHeader("Content-Type");
        $content_length = $img_browser->getResponseHeader("Content-Length");

        if (in_array($content_type, self::$allowed_image_types) && $content_length >= self::$min_filesize && $content_length <= self::$max_filesize)
        {
          $fetch_images[] = array("src" => $img_src, "xpath" => $dom_img->item($i)->getNodePath());
          continue;
        }
      }
    }

    // fetch images
    if (!count($fetch_images))
    {
      $message = "No suitable image found (among " . $total_images . " available) on this link: " . $this->link;
      sfContext::getInstance()->getLogger()->info($message);
      return false;
    }

    //D::dump($fetch_images);

    for ($i = 0; $i < count($fetch_images); $i++) {

      $img_browser = new ContentBrowser();
      if (!$img_browser->get($fetch_images[$i]["src"])->responseIsError() &&
        in_array($img_browser->getResponseHeader("Content-Type"), self::$allowed_image_types))
      {
        $this->removeKeyIfImageIsSmallOrError = false;
        $result = $this->processAsSingleImage($img_browser);

        if ($result)
          break;
      }
    }

    return (bool) $result;

    /*
      // try to find related tags or category
      $xpath = new DOMXpath($dom);
      //D::dump_dom($dom);

      $xpathquery = "//text()[contains(translate(.,'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 'MARVEL BOOK')]";
      $elements = $xpath->query($xpathquery);
      D::dump($elements->length);
      if($elements->length > 0){
      D::dump($elements->item(0)->getNodePath());

      foreach($elements as $element){
      print "Found: " .$element->nodeValue."<br />";
      }

      }
     */



// example 1: for everything with an id
//$elements = $xpath->query("//*[@id]");
// example 2: for node data in a selected id
//$elements = $xpath->query("/html/body/div[@id='yourTagIdHere']");
// example 3: same as above with wildcard
//$elements = $xpath->query("*[@id=content]");
//D::dump_dom($elements);
//XPath: /html/body/div[2]/div[3]/div[1]/center[1]/img
  }

  public function getFullImageSrcFromDom($dom)
  {

    $dom_link = $dom->documentElement->getElementsByTagName("link");
    if ($dom_link_count = $dom_link->length)
    {
      for ($i = 0; $i < $dom_link_count; $i++) {
        if ($dom_link->item($i)->getAttribute("rel") == "image_src")
        {
          $image_src = $dom_link->item($i)->getAttribute("href");
          $full_image_src = dirname($this->link) . "/" . $image_src;
          break;
        }
      }
    }

    return (!empty($full_image_src) ? $full_image_src : false);
  }

  public function findTags($image_key)
  {

    // todo: scan dom to find "category:", "tags:", meta title, ...
    /* if ($dom = Images::getCachedDom($image_key))
      {
      $tags_dom = array();

      sfContext::getInstance()->getLogger()->info("Got cached DOM object for image_key = " . $image_key);
      } */

    $data = parent::$redis->hgetall($image_key);

    if (!$data["link"])
      return false;

    $this->b = new ContentBrowser();


    /*
     * StumbleUpon
     * Sample URL: http://www.stumbleupon.com/services/1.01/badge.getinfo?url=http://www.treehugger.com/
     * YQL sample URL: SELECT content FROM html WHERE url="http://www.stumbleupon.com/url/www.flickr.com/photos/passiveaggressive/3642661392/sizes/o/" and xpath='//ul[@class="listTopics"]/li/a'
     */

    //$data["link"] = "http://www.flickr.com/photos/passiveaggressive/3642661392/sizes/o/";

    $request_url = "http://www.stumbleupon.com/services/1.01/badge.getinfo?url=" . $data["link"];

    $tags = array();

    sfContext::getInstance()->getLogger()->info("Stumbleupon: Request info about submission");

    try {

      if (!$this->b->get($request_url)->responseIsError())
      {
        if (null == $response = json_decode($this->b->getResponseText(), true))
        {
          sfContext::getInstance()->getLogger()->err("Stumbleupon: Incorrect format of response here: " . $request_url);
        } elseif ($response["result"]["in_index"])
        {

          //D::dump($response);
          $yql_url = $response["result"]["info_link"];
          $yql_xpath = '//ul[@class="listTopics"]/li/a';

          $yql_data_table = new YQLDataTable("html");
          $fetched_data = $yql_data_table->select(array("content"), 'url="' . $yql_url . '" and xpath=\'' . $yql_xpath . '\'');

          if ($fetched_data && $fetched_data->a)
          {
            $tags = (is_array($fetched_data->a) ? $fetched_data->a : array($fetched_data->a));
            $tags = Tags::filterUselessTags($tags);

            //D::dump($tags);

            sfContext::getInstance()->getLogger()->info("Stumbleupon: YQL found these tags: " . implode(", ", $tags));
            Tags::setTmpTags($image_key, Tags::SRC_STUMBLEUPON, $tags);
          } else
          {
            sfContext::getInstance()->getLogger()->warning("Stumbleupon: YQL couldn't find any tags here: " . $yql_url . " ; xpath: " . $yql_xpath);
          }
        } else
        {
          sfContext::getInstance()->getLogger()->warning("Stumbleupon: This link doesn't exist in Stumbleupon. Request url: " . $request_url);
        }
      }
    } catch (Exception $e) {
      sfContext::getInstance()->getLogger()->warning("Stumbleupon: Could not to complete search. Connection time-out or something!");
    }


    /*
     * Delicious tags
     * Get tags for URL - http://feeds.delicious.com/v2/json/urlinfo/data?hash=md5(http://...)
     */

    $request_url = "http://feeds.delicious.com/v2/json/urlinfo/data?hash=" . md5($data["link"]);

    $tags = $tmp_tags = array();

    sfContext::getInstance()->getLogger()->info("Delicious: Search this submission in delicious index");

    try {
      if (!$this->b->get($request_url)->responseIsError())
      {
        if (!$response = json_decode($this->b->getResponseText(), true))
        {
          sfContext::getInstance()->getLogger()->warning("Delicious: Doesn't have this link in index. Link: " . $data["link"] . " ; Request url: " . $request_url);
        } else
        {

          $tags = $response[0]["top_tags"];

          $tags = Tags::filterUselessTags($tags, false);
          $tags = array_intersect_key($response[0]["top_tags"], array_flip($tags));
          $tags = Tags::filterTagsByCount($tags, 20, true);
          //D::dump($tags);

          if (count($tags))
          {
            sfContext::getInstance()->getLogger()->info("Delicious: Found these tags: " . implode(", ", $tags));
          } else
          {
            sfContext::getInstance()->getLogger()->warning("Delicious: Couldn't find any tags for this link: " . $data["link"]);
          }

          Tags::setTmpTags($image_key, Tags::SRC_DELICIOUS, $tags);
        }
      }
    } catch (Exception $e) {
      sfContext::getInstance()->getLogger()->warning("Delicious: Could not to complete search. Connection time-out or something!");
    }

    /*
     * Twitter search. Hashtags in twitter can be used as tags
     * Search for url or exact title - http://apiwiki.twitter.com/w/page/22554756/Twitter-Search-API-Method:-search
     */

    $request_urls[] = "http://search.twitter.com/search.json?rpp=100&result_type=mixed&q=" . urlencode($data["link"]);

    // Search by title can very unspecific, when title is short
    if (mb_strlen($data["title"]) > 15)
    {
      $request_urls[] = "http://search.twitter.com/search.json?rpp=100&result_type=mixed&q=" . urlencode('"' . $data["title"] . '"');
    }

    $tags = $tmp_tags = array();

    try {
      foreach ($request_urls as $request_url) {

        sfContext::getInstance()->getLogger()->info("Twitter: Search this submission link/title in tweets");

        if (!$this->b->get($request_url)->responseIsError())
        {
          if (null == $response = json_decode($this->b->getResponseText(), true))
          {
            sfContext::getInstance()->getLogger()->err("Twitter: Incorrect format of response here: " . $request_url);
            break;
          }

          //D::dump($request_url);
          //D::dump($response);

          if (count($response["results"]))
          {
            foreach ($response["results"] as $result) {
              if (preg_match_all("/#([\\d\\w]+)/", $result["text"], $out))
              {
                $tags = array_merge($tags, $out[1]);
                //D::dump($out);
              }
            }
          }
        }
      }

      $tags = Tags::filterUselessTags($tags);
      $tags = Tags::filterTagsByCount($tags);

      if (count($tags))
      {
        sfContext::getInstance()->getLogger()->info("Twitter: Found these tags: " . implode(", ", $tags));
        Tags::setTmpTags($image_key, Tags::SRC_TWITTER, $tags);
      } else
      {
        sfContext::getInstance()->getLogger()->warning("Twitter: Couldn't find any tags for this link: " . $data["link"]);
      }
    } catch (Exception $e) {
      sfContext::getInstance()->getLogger()->warning("Twitter: Could not to complete search. Connection time-out or something!");
    }


    /*
     * Digg topics/tags (it is useless when we fetch data from digg only, because tags are already saved)
     * Get topics - http://developers.digg.com/version2/story-getinfo
     */

    $tags = array();

    sfContext::getInstance()->getLogger()->info("Digg: Search this submission in Digg index");

    try {
      $digg = new Custom_Services_Digg2;
      $digg->setVersion('2.0');

      $result = $digg->story->getInfo(array(
          'links' => urlencode($data["link"]))
      );

      //D::dump($result);
      if ($result->count)
      {
        foreach ($result->stories as $story) {
          if ($story->topic->name != "*")
            $tags[] = $story->topic->name;
        }
      }

      $tags = Tags::filterUselessTags($tags);
 
      if (count($tags))
      {
        sfContext::getInstance()->getLogger()->info("Digg: Found these topics/tags: " . implode(", ", $tags));
        Tags::setTmpTags($image_key, Tags::SRC_DIGG, $tags);
      } else
      {
        sfContext::getInstance()->getLogger()->warning("Digg: Couldn't find any topics/tags for this link: " . $data["link"]);
      }
    } catch (Exception $e) {
      sfContext::getInstance()->getLogger()->warning("Digg: Could not to complete search. Connection time-out or something!");
    }


    /*
     * Reddit
     * a) search for link by url: http://www.reddit.com/api/info.json?url=http%3A%2F%2Fi.imgur.com%2FM52mw.png
     * b) get subreddits
     * c) get subreddits of the related links
     * YQL is forbidden for some things because of reddit's robots.txt, but it is allowed to fetch reddit pages directly
     */

    //$data["link"] = "http://imgur.com/vCxQY.jpg";
    //$data["link"] = "http://imgur.com/mCUfG.jpg";

    $request_url = "http://www.reddit.com/api/info.json?url=" . urlencode($data["link"]);
    $subreddits_xpath = '//div[contains(concat(" ", @class, " "), " entry ")]/p[@class="tagline"]/a[contains(concat(" ", @class, " "), " subreddit ")]';
    //$xpathquery_urls = '//div[contains(concat(" ", @class, " "), " entry ")]/ul/li/a[contains(concat(" ", @class, " "), " comments ")]';

    $tags = $tmp_tags = $yql_urls = array();

    sfContext::getInstance()->getLogger()->info("Reddit: Search this submission in reddit index: " . $request_url);

    try {
      if (!$this->b->get($request_url)->responseIsError())
      {

        if (null == $response = json_decode($this->b->getResponseText(), true))
        {
          sfContext::getInstance()->getLogger()->err("Reddit: Incorrect format of response here: " . $request_url);
        } elseif (count($response["data"]["children"]))
        {

          // Get subreddits of the main link
          foreach ($response["data"]["children"] as $child) {
            $tags[] = $child["data"]["subreddit"];
            $yql_urls[] = "http://www.reddit.com" . str_replace("/comments/", "/related/", $child["data"]["permalink"]);
          }

          sfContext::getInstance()->getLogger()->info("Reddit: Found these tags inside html: " . implode(", ", $tags));

          //D::dump($yql_urls);

          if (count($yql_urls))
          {
            $yql_data_table = new YQLDataTable("html");
            $fetched_data = $yql_data_table->select(array("content"), 'url in ("' . implode('", "', $yql_urls) . '") and xpath=\'' . $subreddits_xpath . '\'');

            //D::dump($yql_urls);
            //D::dump($fetched_data);
            if ($fetched_data && count($fetched_data->a))
            {
              $tmp_tags = array_merge($tmp_tags, $fetched_data->a);
            }
          }

          //D::dump($tags);
          //D::dump($tmp_tags);

          if (count($tmp_tags) || count($tags))
          {
            $tags = array_merge($tags, $tmp_tags);

            $tags = Tags::filterUselessTags($tags);
            $tags = Tags::filterTagsByCount($tags);
            //D::dump($tags);

            Tags::setTmpTags($image_key, Tags::SRC_REDDIT, $tags);

            sfContext::getInstance()->getLogger()->info("Reddit: YQL found these tags on related pages: " . implode(", ", $tmp_tags));
          } else
          {
            sfContext::getInstance()->getLogger()->warning("Reddit: YQL couldn't find any tags on related pages: " . implode(' &nbsp;&nbsp;&nbsp; ', $yql_urls) . " ; xpath: " . $subreddits_xpath);
          }
        } else
        {
          sfContext::getInstance()->getLogger()->warning("Reddit: YQL couldn't find this link on reddit.");
        }
      }
    } catch (Exception $e) {
      sfContext::getInstance()->getLogger()->warning("Reddit: Could not to complete search. Connection time-out or something!");
    }
  }

}
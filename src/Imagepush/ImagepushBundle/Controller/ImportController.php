<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Document\Tag;
use Imagepush\ImagepushBundle\Document\LatestTag;
use Imagepush\ImagepushBundle\Document\Link;
use Imagepush\ImagepushBundle\Document\ProcessedHash;

class ImportController extends Controller
{

    /**
     * @Route("/", name="importIndex")
     * @Template("::base.html.twig")
     */
    public function indexAction()
    {
        // Whether to get images from S3 to get filesize (expensive operation from outside of Amazon!)
        $getImageFileSize = false;

        //$this->importTags();
        //$this->importLatestTags();
        //$this->importLinks(); // indexed, failed
        //$this->importImages(999999, $getImageFileSize);
        $this->importProcessedHashes();

        echo "All done :)";

        return array();
    }

    /**
     * Import tags
     */
    private function importTags()
    {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $redis = $this->get('snc_redis.default_client');
        $i = 0;

        $dm->getDocumentCollection("ImagepushBundle:Tag")->drop();

        $tags = $this->get('imagepush.tags.manager')->getAllHumanTagsWithIds();

        //\D::dump($tags);
        $importedTags = array();
        if (count($tags)) {
            foreach ($tags as $legacyKey => $tag) {
                $text = $tag;

                // replace
                if (strstr($legacyKey, "_replace")) {
                    //\D::dump($redis->get(str_replace("_replace", "",$legacyKey)));
                    //\D::dump($redis->get($text));
                    $legacyKey = $tag;
                    $text = $redis->get($tag);
                }

                // replace newline
                $text = str_replace("\n", " ", $text);

                if (!empty($text) && !in_array($text, $importedTags)) {
                    $importedTags[] = $text;
                } else {
                    continue;
                }

                $new = new Tag();
                $new->setLegacyKey($legacyKey);
                $new->setText($text);

                //$count = $redis->zscore("tag_usage", $legacyKey);
                //$availableCount = $redis->zcard("image_list:". $legacyKey);
                //$upcomingCount = $redis->zcard("upcoming_image_list:". $legacyKey);
                //$new->setUsedInAvailable((int)$availableCount);
                //$new->setUsedInUpcoming((int)$upcomingCount);

                $dm->persist($new);

                if (++$i % 100 == 0) {
                    $dm->flush();
                    $dm->clear();
                }
            }

            $dm->flush();
            $dm->clear();
        }
    }

    /**
     * Import images
     */
    private function importImages($limit = 10, $getImageFileSize = false)
    {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $fs = $this->get('knp_gaufrette.filesystem_map')->get('images');

        $i = 0;
        $missing = array();

        $dm->getDocumentCollection("ImagepushBundle:Image")->drop();

        $allImages = array(
            "current" => $this->get('imagepush.images.manager')->getImages("current", $limit),
            "upcoming" => $this->get('imagepush.images.manager')->getImages("upcoming", $limit)
        );

        //\D::dump($allImages);

        foreach ($allImages as $imageGroup => $images) {

            if (count($images)) {
                foreach ($images as $image) {

                    $createdMW = $createdTW = $createdAW = 0;
                    $createdMH = $createdTH = $createdAH = 0;

                    if (empty($image["id"])) {
                        $missing[] = $image;
                        continue;
                        //\D::dump($image);
                    }

                    $new = new Image();
                    $new->setIsAvailable($imageGroup == "current");
                    $new->setId($image["id"]);
                    $new->setLink($image["link"]);
                    $new->setTitle($image["title"]);
                    $new->setSlug($image["slug"]);
                    if (!empty($image["timestamp"])) {
                        $new->setTimestamp((int) $image["timestamp"]);
                    }
                    if (!empty($image["m_file"])) {
                        $file = $image["m_file"];
                    } elseif (!empty($image["file"])) {
                        $file = $image["file"];
                    } else {
                        $file = "";
                        $missing[] = $image;
                        continue;
                    }

                    $new->setFile($file);

                    $ext = strtolower(strchr($file, "."));
                    if ($ext == ".gif") {
                        $new->setMimeType("image/gif");
                    } elseif ($ext == ".png") {
                        $new->setMimeType("image/png");
                    } else {
                        $new->setMimeType("image/jpeg");
                    }

                    if (!empty($image["source_type"])) {
                        $new->setSourceType($image["source_type"]);
                    } else {
                        $new->setSourceType("digg");
                    }
                    if (!empty($image["source_tags"])) {
                        $new->setSourceTags(json_decode($image["source_tags"], true));
                    }
                    if (!empty($image["m_width"])) {
                        $createdMW = $image["m_width"];
                        //$new->setMWidth($image["m_width"]);
                    }
                    if (!empty($image["m_height"])) {
                        $createdMH = $image["m_height"];
                        //$new->setMHeight($image["m_height"]);
                    }
                    if (!empty($image["thumb_width"])) {
                        $createdTW = $image["thumb_width"];
                        //$new->setTWidth($image["thumb_width"]);
                    }
                    if (!empty($image["thumb_height"])) {
                        $createdTH = $image["thumb_height"];
                        //$new->setTHeight($image["thumb_height"]);
                    }
                    if (!empty($image["a_width"])) {
                        $createdAW = $image["a_width"];
                        //$new->setAWidth($image["a_width"]);
                    }
                    if (!empty($image["a_height"])) {
                        $createdAH = $image["a_height"];
                        //$new->setAHeight($image["a_height"]);
                    }

                    if ($createdMW) {
                        // main
                        $filesize = 0;

                        if ($getImageFileSize) {
                            try {
                                $filesize = strlen($fs->read("in/463x1548/i/" . $file));
                            } catch (\Exception $e) {
                                $this->get('logger')->err('[IMPORT] ' . $e->getMessage());
                            }
                        }

                        $new->addThumbs("in", "463x1548", $createdMW, $createdMH, $filesize);
                    }

                    if ($createdTW) {
                        // thumb
                        $filesize = 0;

                        if ($getImageFileSize) {
                            try {
                                $filesize = strlen($fs->read("out/140x140/i/" . $file));
                            } catch (\Exception $e) {
                                $this->get('logger')->err('[IMPORT] ' . $e->getMessage());
                            }
                        }

                        $new->addThumbs("out", "140x140", $createdTW, $createdTH);
                    }

                    if ($createdAW) {
                        // article
                        $filesize = 0;

                        if ($getImageFileSize) {
                            try {
                                $filesize = strlen($fs->read("in/625x2090/i/" . $file));
                            } catch (\Exception $e) {
                                $this->get('logger')->err('[IMPORT] ' . $e->getMessage());
                            }
                        }

                        $new->addThumbs("in", "625x2090", $createdAW, $createdAH);
                    }


                    if (!empty($image["_tags"])) {

                        $tags = array();

                        foreach ($image["_tags"] as $oneTag) {
                            $tag = $dm->getRepository("ImagepushBundle:Tag")->findOneBy(array("text" => $oneTag));
                            if ($tag) {
                                $tags[] = $oneTag;
                                $new->addTagsRef($tag);
                                $tag->addImagesRef($new);
                                if ($imageGroup == "current") {
                                    $tag->setUsedInAvailable($tag->getUsedInAvailable() + 1);
                                } else {
                                    $tag->setUsedInUpcoming($tag->getUsedInUpcoming() + 1);
                                }
                                $dm->persist($tag);
                            } else {
                                \D::dump($oneTag);
                            }
                        }

                        //if (count($tags)) {
                        $new->setTags($tags);
                        //}
                    }

                    $dm->persist($new);

                    if (++$i % 300 == 0) {
                        $dm->flush();
                        $dm->clear();
                    }
                }

                $dm->flush();
                $dm->clear();
            }
        }

        //\D::dump($missing);
    }

    /**
     * Import latest tags
     */
    private function importLatestTags()
    {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $redis = $this->get('snc_redis.default_client');

        $i = 0;

        $dm->getDocumentCollection("ImagepushBundle:LatestTag")->drop();

        // latest trend: get last 300 from the list and count how often they used
        $latestTags = $redis->lrange("latest_tags", 0, -1); // put 0 instead of -300 for complete import
        //\D::dump($latestTags);
        //die();
        if (count($latestTags)) {
            foreach ($latestTags as $latestTag) {
                $new = new LatestTag();
                $new->setTimestamp(time());

                $tag = $dm->getRepository("ImagepushBundle:Tag")->findOneBy(array("legacyKey" => $latestTag));
                //\D::dump($tag);
                if ($tag) {
                    $new->setTag($tag);
                    $dm->persist($new);
                } else {
                    //\D::dump($latestTag);
                }

                if (++$i % 100 == 0) {
                    $dm->flush();
                    $dm->clear();
                }
            }

            $dm->flush();
            $dm->clear();
        }
    }

    /**
     * Import links
     */
    private function importLinks()
    {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $redis = $this->get('snc_redis.default_client');

        $i = 0;

        $dm->getDocumentCollection("ImagepushBundle:Link")->drop();

        $allLinks = array(
            "indexed" => $redis->smembers("indexed_links"),
            "failed" => $redis->smembers("failed_links")
        );

        //\D::dump($allLinks);
        //die();

        if (count($allLinks)) {
            foreach ($allLinks as $status => $links) {
                foreach ($links as $link) {
                    $new = new Link();
                    $new->setStatus($status);
                    $new->setLink($link);
                    $dm->persist($new);
                }

                if (++$i % 500 == 0) {
                    $dm->flush();
                    $dm->clear();
                }
            }

            $dm->flush();
            $dm->clear();
        }
    }

    /**
     * Import processed hashes
     */
    private function importProcessedHashes()
    {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $redis = $this->get('snc_redis.default_client');

        $i = 0;

        $dm->getDocumentCollection("ImagepushBundle:ProcessedHash")->drop();

        $allHashes = $redis->smembers("processed_image_hash");

        //\D::dump($allHashes);
        //die();

        if (count($allHashes)) {
            foreach ($allHashes as $hash) {
                $new = new ProcessedHash();
                $new->setHash($hash);
                $dm->persist($new);


                if (++$i % 1000 == 0) {
                    $dm->flush();
                    $dm->clear();
                }
            }

            $dm->flush();
            $dm->clear();
        }
    }

}

<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\External\CustomStrings;

class RedditFetcher extends AbstractFetcher implements FetcherInterface
{

    public function __construct($container)
    {
        parent::__construct($container, "reddit");
    }

    /**
     * Check if item is good enough to be saved (Score and unique link hash)
     */
    public function isWorthToSave($item)
    {

        if (!isset($item->title) || CustomStrings::isForbiddenTitle($item->title) || !parent::isWorthToSave($item)) {
            return false;
        }

        $minScore = $this->getParameter("min_score", 100);

        // @codingStandardsIgnoreStart
        $worthToSave = (
            isset($item->score) &&
            $item->score >= $minScore &&
            isset($item->over_18) &&
            $item->over_18 != true &&
            isset($item->is_self) &&
            $item->is_self != true &&
            false === (bool) $this->dm->getRepository('ImagepushBundle:Link')->isIndexedOrFailed($item->url) &&
            false === (bool) $this->dm->getRepository('ImagepushBundle:Image')->findOneBy(array("link" => $item->url))
            );
        // @codingStandardsIgnoreEnd

        if ($worthToSave) {
            $log = sprintf("[Reddit] YES. Score: %d. Link: %s. Title: %s", $item->score, $item->url, $item->title);
            $this->logger->info($log);
            $this->output[] = $log;

            return true;
        } else {
            $log = sprintf("[Reddit] NO. Score: %d. Link: %s. Title: %s", $item->score, $item->url, $item->title);
            $this->logger->info($log);
            $this->output[] = $log;

            return false;
        }
    }

    /**
     * Check and save
     *
     * @return boolean
     *
     * @throws \Exception
     */
    public function checkAndSaveData()
    {

        if (!isset($this->data) || $this->data == false) {
            return false;
        }

        foreach ($this->data as $item) {

            if (!$this->isWorthToSave($item)) {
                continue;
            }

            $image = new Image();
            $image->setSourceType("reddit");
            $image->setLink($item->url);
            $image->setCreatedAt(new \DateTime('@'.(int) $item->created));
            $image->setTitle(CustomStrings::cleanTitle($item->title));
            $image->setSlug(CustomStrings::slugify($item->title));

            if (!empty($item->subreddit)) {
                $tag = CustomStrings::cleanTag($item->subreddit);
                $image->setSourceTags((array) $tag);
            }

            try {
                // increment id
                $nextId = $this->dm->getRepository('ImagepushBundle:Image')->getNextId();

                if ($nextId) {
                    $image->setId($nextId);
                } else {
                    throw new \Exception("Can't find max image ID to increment");
                }

                $this->dm->persist($image);
                $this->dm->flush();
                $this->dm->refresh($image);

                $this->savedCounter++;
            } catch (\Exception $e) {
                $this->logger->err(sprintf("Link: %s has not been saved. Error was: %s", $item->url, $e->getMessage()));
            }
        }
    }

    /**
     * @return type
     */
    public function run()
    {

        $urls = $this->getParameter('urls');
        //\D::debug($urls);

        foreach ($urls as $url) {

            $this->data = array();

            $this->delayBeforeNextApiCall();

            $content = $this->container->get('imagepush.fetcher.content');
            $content->setUserAgent(true, 'by /u/imagepush.to');

            $response = $content->getRequest($url);

            //$response["Content"] = self::$fakedContent;

            if (empty($response["Content"])) {
                $this->output[] = sprintf("[Reddit] %s: Reddit replied with error code: %s", date(DATE_RSS), $response);
                continue;
            }

            $data = json_decode($response["Content"], false);

            if ($data === null) {
                $this->output[] = sprintf("[Reddit] %s: Response is not json", date(DATE_RSS));
                continue;
            }

            if (empty($data->data->children[0]->data)) {
                $this->output[] = sprintf("[Reddit] %s: Response is just wrong", date(DATE_RSS));
                continue;
            }

            foreach ($data->data->children as $item) {
                $this->data[] = $item->data;
            }

            $this->fetchedCounter += count($this->data);

            $this->checkAndSaveData();
        }

        if ($this->fetchedCounter && $this->savedCounter) {
            $this->output[] = sprintf("[Reddit] %s: %d of %d items have been saved.", date(DATE_RSS), $this->savedCounter, $this->fetchedCounter);
        } elseif ($this->fetchedCounter && !$this->savedCounter) {
            $this->output[] = sprintf("[Reddit] %s: %d sources received, but nothing has been saved (all filtered out).", date(DATE_RSS), $this->fetchedCounter);
        } else {
            $this->output[] = sprintf("[Reddit] %s: Reddit replied with error", date(DATE_RSS));
        }

        return $this->output;
    }

    //public static $fakedContent = '{"kind": "Listing", "data": {"modhash": "", "children": [{"kind": "t3", "data": {"domain": "avesom.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x2m64", "clicked": false, "title": "Some Of The Popular Building Photography", "num_comments": 2, "score": 22, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://f.thumbs.redditmedia.com/RYY79pNTdaSasBMS.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 6, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x2m64/some_of_the_popular_building_photography/", "name": "t3_x2m64", "created": 1343163074.0, "url": "http://avesom.com/some-of-the-popular-building-photography-of-all-time-pics/", "author_flair_text": null, "author": "heretohelpyoupal", "created_utc": 1343137874.0, "media": null, "num_reports": null, "ups": 28}}, {"kind": "t3", "data": {"domain": "imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x2p20", "clicked": false, "title": "The making of James Holmes", "num_comments": 3, "score": 8, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://b.thumbs.redditmedia.com/I9_OCevWVbg3i3m1.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 1, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x2p20/the_making_of_james_holmes/", "name": "t3_x2p20", "created": 1343166446.0, "url": "http://imgur.com/sjwuK", "author_flair_text": null, "author": "tomcisar", "created_utc": 1343141246.0, "media": null, "num_reports": null, "ups": 9}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x1s8n", "clicked": false, "title": "Not many people know the difference.", "num_comments": 12, "score": 76, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://c.thumbs.redditmedia.com/8DenH_Y6JHyjd8db.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 31, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x1s8n/not_many_people_know_the_difference/", "name": "t3_x1s8n", "created": 1343119258.0, "url": "http://i.imgur.com/BdwEL.png", "author_flair_text": null, "author": "MrJQuinn", "created_utc": 1343094058.0, "media": null, "num_reports": null, "ups": 107}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x236z", "clicked": false, "title": "What the fuck...", "num_comments": 4, "score": 31, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://f.thumbs.redditmedia.com/V_wfL5fdI2h5TCX5.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 4, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x236z/what_the_fuck/", "name": "t3_x236z", "created": 1343130364.0, "url": "http://i.imgur.com/CeGhj.jpg", "author_flair_text": null, "author": "mads123j", "created_utc": 1343105164.0, "media": null, "num_reports": null, "ups": 35}}, {"kind": "t3", "data": {"domain": "picturesw.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x2bpm", "clicked": false, "title": "Meanwhile in Ireland", "num_comments": 1, "score": 16, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://d.thumbs.redditmedia.com/qa4ZjWjEUdk2xBw-.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 2, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x2bpm/meanwhile_in_ireland/", "name": "t3_x2bpm", "created": 1343142837.0, "url": "http://www.picturesw.com/2012/07/meanwhile-in-ireland.html", "author_flair_text": null, "author": "turner13", "created_utc": 1343117637.0, "media": null, "num_reports": null, "ups": 18}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x2n3y", "clicked": false, "title": "When you see it....", "num_comments": 0, "score": 4, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://d.thumbs.redditmedia.com/OX33qZjZnJD2OPO3.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 3, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x2n3y/when_you_see_it/", "name": "t3_x2n3y", "created": 1343164187.0, "url": "http://i.imgur.com/Liezg.jpg", "author_flair_text": null, "author": "mads123j", "created_utc": 1343138987.0, "media": null, "num_reports": null, "ups": 7}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x2o6v", "clicked": false, "title": "Insect ID-Atlanta, GA", "num_comments": 1, "score": 3, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://f.thumbs.redditmedia.com/pTc-XBzRepmOuQbY.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 0, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x2o6v/insect_idatlanta_ga/", "name": "t3_x2o6v", "created": 1343165474.0, "url": "http://i.imgur.com/dYQPu.jpg", "author_flair_text": null, "author": "missmalarkey", "created_utc": 1343140274.0, "media": null, "num_reports": null, "ups": 3}}, {"kind": "t3", "data": {"domain": "imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x2skh", "clicked": false, "title": "For my cake day, my cousin and I as Batman and Catwoman", "num_comments": 2, "score": 2, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://c.thumbs.redditmedia.com/MI5-dAm0nmT3c5Ht.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 1, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x2skh/for_my_cake_day_my_cousin_and_i_as_batman_and/", "name": "t3_x2skh", "created": 1343170017.0, "url": "http://imgur.com/BNeib", "author_flair_text": null, "author": "jesiro", "created_utc": 1343144817.0, "media": null, "num_reports": null, "ups": 3}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x0oar", "clicked": false, "title": "Teotihuacan, Mexico", "num_comments": 6, "score": 138, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://a.thumbs.redditmedia.com/krB-W93p1p2HBqQB.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 19, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x0oar/teotihuacan_mexico/", "name": "t3_x0oar", "created": 1343081408.0, "url": "http://i.imgur.com/NDQeZ.jpg", "author_flair_text": null, "author": "AjdinSamurai", "created_utc": 1343056208.0, "media": null, "num_reports": null, "ups": 157}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x2sye", "clicked": false, "title": "There goes my childhood.", "num_comments": 0, "score": 1, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://f.thumbs.redditmedia.com/hN4hns00qOS1eoCA.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 0, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x2sye/there_goes_my_childhood/", "name": "t3_x2sye", "created": 1343170408.0, "url": "http://i.imgur.com/gkpX3.jpg", "author_flair_text": null, "author": "SolidSyco", "created_utc": 1343145208.0, "media": null, "num_reports": null, "ups": 1}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x0owz", "clicked": false, "title": "Mount Fuji, Japan", "num_comments": 1, "score": 61, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://b.thumbs.redditmedia.com/rv9FrGqWtN2iP1Og.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 14, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x0owz/mount_fuji_japan/", "name": "t3_x0owz", "created": 1343082063.0, "url": "http://i.imgur.com/jOqpt.jpg", "author_flair_text": null, "author": "AjdinSamurai", "created_utc": 1343056863.0, "media": null, "num_reports": null, "ups": 75}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x0omr", "clicked": false, "title": "Prague, Czech Republic", "num_comments": 1, "score": 60, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://b.thumbs.redditmedia.com/mGI_8WBMMwIDocNU.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 17, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x0omr/prague_czech_republic/", "name": "t3_x0omr", "created": 1343081775.0, "url": "http://i.imgur.com/ARtsM.jpg", "author_flair_text": null, "author": "AjdinSamurai", "created_utc": 1343056575.0, "media": null, "num_reports": null, "ups": 77}}, {"kind": "t3", "data": {"domain": "imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x24ru", "clicked": false, "title": "i work in chaos so i like to keep my desk drawer tidy.", "num_comments": 0, "score": 5, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://a.thumbs.redditmedia.com/Iczdw2TPhoA4shfB.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 3, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x24ru/i_work_in_chaos_so_i_like_to_keep_my_desk_drawer/", "name": "t3_x24ru", "created": 1343132158.0, "url": "http://imgur.com/hf2yA", "author_flair_text": null, "author": "bodessa", "created_utc": 1343106958.0, "media": null, "num_reports": null, "ups": 8}}, {"kind": "t3", "data": {"domain": "i.minus.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x13lr", "clicked": false, "title": "Now that\'s a bounce [GIF]", "num_comments": 7, "score": 23, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://a.thumbs.redditmedia.com/y8C7bCUPF7a7dpUf.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 16, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x13lr/now_thats_a_bounce_gif/", "name": "t3_x13lr", "created": 1343095896.0, "url": "http://i.minus.com/ibl9QVaXUReG3w.gif", "author_flair_text": null, "author": "Salival81", "created_utc": 1343070696.0, "media": null, "num_reports": null, "ups": 39}}, {"kind": "t3", "data": {"domain": "botcrawl.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x2amr", "clicked": false, "title": "Colorado Memorial Batman Bat Image", "num_comments": 0, "score": 2, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "default", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 0, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x2amr/colorado_memorial_batman_bat_image/", "name": "t3_x2amr", "created": 1343140674.0, "url": "http://botcrawl.com/batman-the-dark-knight-rises-premiere-fatal-bombing-and-shooting-witness-video-and-compelte-information/colorado-memorial-batman-bat/", "author_flair_text": null, "author": "canthatbeit", "created_utc": 1343115474.0, "media": null, "num_reports": null, "ups": 2}}, {"kind": "t3", "data": {"domain": "flickr.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x23rh", "clicked": false, "title": "La Parroquia - San Miguel de Allende [1024x683]", "num_comments": 0, "score": 3, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://b.thumbs.redditmedia.com/-5hmCqHPeR7VKgPI.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 0, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x23rh/la_parroquia_san_miguel_de_allende_1024x683/", "name": "t3_x23rh", "created": 1343130973.0, "url": "http://www.flickr.com/photos/phil_marion/4036443609/sizes/o/in/photostream/", "author_flair_text": null, "author": "arcporn", "created_utc": 1343105773.0, "media": null, "num_reports": null, "ups": 3}}, {"kind": "t3", "data": {"domain": "flickr.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x236l", "clicked": false, "title": "Roadside memorial marker", "num_comments": 0, "score": 3, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://f.thumbs.redditmedia.com/Q_BD_t90Gc6sTdSw.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 0, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x236l/roadside_memorial_marker/", "name": "t3_x236l", "created": 1343130356.0, "url": "http://www.flickr.com/photos/phil_marion/3219812268/sizes/o/in/photostream/", "author_flair_text": null, "author": "cpporn", "created_utc": 1343105156.0, "media": null, "num_reports": null, "ups": 3}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x0pr2", "clicked": false, "title": "Montreal, Canada", "num_comments": 5, "score": 31, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://a.thumbs.redditmedia.com/ejf3BehqHMt577-B.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 6, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x0pr2/montreal_canada/", "name": "t3_x0pr2", "created": 1343082957.0, "url": "http://i.imgur.com/hPmUv.jpg", "author_flair_text": null, "author": "AjdinSamurai", "created_utc": 1343057757.0, "media": null, "num_reports": null, "ups": 37}}, {"kind": "t3", "data": {"domain": "imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x0nu0", "clicked": false, "title": "Sagrada Familia-Barcelona", "num_comments": 0, "score": 35, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://d.thumbs.redditmedia.com/EOee667eHgn1-Xqo.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 8, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x0nu0/sagrada_familiabarcelona/", "name": "t3_x0nu0", "created": 1343080935.0, "url": "http://imgur.com/q4CRi", "author_flair_text": null, "author": "AjdinSamurai", "created_utc": 1343055735.0, "media": null, "num_reports": null, "ups": 43}}, {"kind": "t3", "data": {"domain": "imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x0usc", "clicked": false, "title": "Check out Neil Degrasse Tyson as a young bad ass hipster", "num_comments": 0, "score": 16, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://a.thumbs.redditmedia.com/1vBk4qPK6nJ25zo0.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 9, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x0usc/check_out_neil_degrasse_tyson_as_a_young_bad_ass/", "name": "t3_x0usc", "created": 1343087891.0, "url": "http://imgur.com/38pwe", "author_flair_text": null, "author": "johnnylogic", "created_utc": 1343062691.0, "media": null, "num_reports": null, "ups": 25}}, {"kind": "t3", "data": {"domain": "fbcdn-sphotos-a.akamaihd.net", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x0n59", "clicked": false, "title": "Temple of the Dawn-Bangkok,Thailand", "num_comments": 2, "score": 21, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://b.thumbs.redditmedia.com/i4GQ05OhWz0pYUcO.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 4, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x0n59/temple_of_the_dawnbangkokthailand/", "name": "t3_x0n59", "created": 1343080245.0, "url": "https://fbcdn-sphotos-a.akamaihd.net/hphotos-ak-ash3/578883_437573426286855_1111643911_n.jpg", "author_flair_text": null, "author": "AjdinSamurai", "created_utc": 1343055045.0, "media": null, "num_reports": null, "ups": 25}}, {"kind": "t3", "data": {"domain": "imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x1a60", "clicked": false, "title": "My intellectual 4 month old son", "num_comments": 0, "score": 6, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://b.thumbs.redditmedia.com/DETE6EudoQCGjIK1.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 0, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x1a60/my_intellectual_4_month_old_son/", "name": "t3_x1a60", "created": 1343101914.0, "url": "http://imgur.com/a/JuXVq", "author_flair_text": null, "author": "IamGraceful", "created_utc": 1343076714.0, "media": null, "num_reports": null, "ups": 6}}, {"kind": "t3", "data": {"domain": "fbcdn-sphotos-a.akamaihd.net", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x1tmq", "clicked": false, "title": "Bengal tiger sprayed with water", "num_comments": 0, "score": 2, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://f.thumbs.redditmedia.com/nd5xI2fiwwgpZrSe.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 1, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x1tmq/bengal_tiger_sprayed_with_water/", "name": "t3_x1tmq", "created": 1343120664.0, "url": "https://fbcdn-sphotos-a.akamaihd.net/hphotos-ak-ash3/599326_10151003531994595_371702103_n.jpg", "author_flair_text": null, "author": "nononotoryuss", "created_utc": 1343095464.0, "media": null, "num_reports": null, "ups": 3}}, {"kind": "t3", "data": {"domain": "i.imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x1tgh", "clicked": false, "title": "The Moon", "num_comments": 0, "score": 3, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://f.thumbs.redditmedia.com/tbmymGPdiQVxEm8k.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 1, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x1tgh/the_moon/", "name": "t3_x1tgh", "created": 1343120505.0, "url": "http://i.imgur.com/pN4qC.jpg", "author_flair_text": null, "author": "thatonegirl435", "created_utc": 1343095305.0, "media": null, "num_reports": null, "ups": 4}}, {"kind": "t3", "data": {"domain": "imgur.com", "banned_by": null, "media_embed": {}, "subreddit": "Images", "selftext_html": null, "selftext": "", "likes": null, "link_flair_text": null, "id": "x0t5z", "clicked": false, "title": "Threw up rainbows when I saw this.", "num_comments": 0, "score": 10, "approved_by": null, "over_18": false, "hidden": false, "thumbnail": "http://a.thumbs.redditmedia.com/6emYsFEWNtv5CjI6.jpg", "subreddit_id": "t5_2qtjz", "edited": false, "link_flair_css_class": null, "author_flair_css_class": null, "downs": 0, "saved": false, "is_self": false, "permalink": "/r/Images/comments/x0t5z/threw_up_rainbows_when_i_saw_this/", "name": "t3_x0t5z", "created": 1343086300.0, "url": "http://imgur.com/jeef2", "author_flair_text": null, "author": "ERICAAAW", "created_utc": 1343061100.0, "media": null, "num_reports": null, "ups": 10}}], "after": "t3_x0t5z", "before": null}}';
}

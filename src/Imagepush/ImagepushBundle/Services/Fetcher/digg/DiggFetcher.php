<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher\Digg;

use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Services\Fetcher\AbstractFetcher; // no need in abstract
use Imagepush\ImagepushBundle\Services\Fetcher\FetcherInterface;
use Imagepush\ImagepushBundle\External\CustomStrings;

class DiggFetcher extends AbstractFetcher implements FetcherInterface
{

    /**
     * Limit for API call. 200 - max, but often fails. Try with 20-40.
     * @param integer $fetchLimit
     */
    public $fetchLimit = 10;

    /**
     * Minimum diggs score to save. 4-5 is OK.
     * @param integer $minDiggs
     */
    public $minDiggs = 1; // 4

    /**
     * Minimum delay between API requests. Set to 30 mins, but cron runs every 5 minutes, so there are 6 attempts in each interval
     * @var integer $minDelay
     */
    public static $minDelay = 10; //1800; // 10 

    /**
     * Recent source data to show in the output.
     */
    public $recentSourceDate;
    public $lastStatus, $lastAccess;

    /**
     * Check if item is good enough to be saved (Digg counts and unique link hash)
     */
    public function isWorthToSave($item)
    {

        if (!isset($item->title) || CustomStrings::isForbiddenTitle($item->title) || !parent::isWorthToSave($item)) {
            return false;
        }

        $isIndexedOrFailed = $this->dm->getRepository('ImagepushBundle:Link')->isIndexedOrFailed($item->link);

        $result = (
            isset($item->diggs) &&
            $item->diggs >= $this->minDiggs &&
            false === $isIndexedOrFailed
            );

        if ($result) {
            echo "<br>isWorthToSave = true";
            \D::dump($item);

            return true;
        } else {
            \D::dump($item);

            return false;
        }
    }

    /**
     * @return boolean 
     */
    public function fetchData()
    {
        /*        $data = <<<DATA
          a:10:{i:0;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:11:"description";s:75:"No One Even Said
          "HEY! Sorry That Happened" Real LosaaaS Here In Dover NH.";s:5:"title";s:131:"Twitgoo - joeyalizio - The 'Other' Gold ChainS Picture.
          14KT Diamond Cut Rope Chain 44 Gr.
          Thats What 'He' Was Trying To Rob From M";s:11:"submit_date";i:1323026627;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:0;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:160:"http://cdn2.diggstatic.com/story/twitgoo_joeyalizio_the_other_gold_chains_picture_14kt_diamond_cut_rope_chain_44_gr_thats_what_he_was_trying_to_rob_from_m/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:156:"http://digg.com/news/entertainment/twitgoo_joeyalizio_the_other_gold_chains_picture_14kt_diamond_cut_rope_chain_44_gr_thats_what_he_was_trying_to_rob_from_m";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:25:"http://twitgoo.com/51v9eu";s:4:"href";s:156:"http://digg.com/news/entertainment/twitgoo_joeyalizio_the_other_gold_chains_picture_14kt_diamond_cut_rope_chain_44_gr_thats_what_he_was_trying_to_rob_from_m";s:2:"id";s:51:"20111204192347:660028ca-1a22-4c71-9b8d-0bfc24407145";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:12:"joeyaliziojr";s:5:"links";a:2:{i:0;O:8:"stdClass":3:{s:4:"date";i:1323032017;s:4:"href";s:21:"http://joeyalizio.com";s:11:"description";s:14:"Joey Alizio Jr";}i:1;O:8:"stdClass":3:{s:4:"date";i:1323032017;s:4:"href";s:21:"http://joeyalizio.net";s:11:"description";s:14:"Joey Alizio Jr";}}s:10:"registered";i:1279337229;s:12:"profileviews";i:0;s:8:"fullname";s:30:"Joey Alizio Jr : Hitman 1972 :";s:4:"icon";s:56:"http://cdn3.diggstatic.com/user/7453867/l.1185509649.png";}}i:1;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:11:"description";s:0:"";s:5:"title";s:20:"el puertillo - FOCUS";s:11:"submit_date";i:1323022876;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:0;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:57:"http://cdn3.diggstatic.com/story/el_puertillo_focus/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:53:"http://digg.com/news/entertainment/el_puertillo_focus";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:56:"http://focuss.ning.com/photo/el-puertillo?context=latest";s:4:"href";s:53:"http://digg.com/news/entertainment/el_puertillo_focus";s:2:"id";s:51:"20111204182116:45f77c7e-eeed-4d63-86d8-4a29f32c79c2";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:7:"mnlmrn1";s:5:"links";a:0:{}s:10:"registered";i:1323021339;s:12:"profileviews";i:0;s:8:"fullname";s:13:"Manuel Moreno";s:4:"icon";s:99:"http://cdn2.diggstatic.com/user/20111204175539:64ba4a4a-eecd-4ba6-a3cc-0759d18237c5/l.908736788.png";}}i:2;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:11:"description";s:0:"";s:5:"title";s:29:"tarde en el puertillo - FOCUS";s:11:"submit_date";i:1323021814;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:0;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:66:"http://cdn1.diggstatic.com/story/tarde_en_el_puertillo_focus/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:62:"http://digg.com/news/entertainment/tarde_en_el_puertillo_focus";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:109:"http://focuss.ning.com/photo/tarde-en-el-puertillo?commentId=6448429%3AComment%3A7001&xg_source=msg_com_photo";s:4:"href";s:62:"http://digg.com/news/entertainment/tarde_en_el_puertillo_focus";s:2:"id";s:51:"20111204180334:dcf065d7-9a49-40ba-ac7b-e6dc7becbfb5";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:7:"mnlmrn1";s:5:"links";a:0:{}s:10:"registered";i:1323021339;s:12:"profileviews";i:0;s:8:"fullname";s:13:"Manuel Moreno";s:4:"icon";s:99:"http://cdn2.diggstatic.com/user/20111204175539:64ba4a4a-eecd-4ba6-a3cc-0759d18237c5/l.908736788.png";}}i:3;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:8:"Politics";s:10:"short_name";s:8:"politics";}s:11:"description";s:22:"Those damned Juans....";s:5:"title";s:23:"We are the Juan Percent";s:11:"submit_date";i:1323025355;s:5:"media";i:2;s:5:"diggs";i:33;s:8:"comments";i:3;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:62:"http://cdn3.diggstatic.com/story/we_are_the_juan_percent/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:8:"Politics";s:10:"short_name";s:8:"politics";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:53:"http://digg.com/news/politics/we_are_the_juan_percent";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:28:"http://i.imgur.com/6s5LB.jpg";s:4:"href";s:53:"http://digg.com/news/politics/we_are_the_juan_percent";s:2:"id";s:51:"20111204190235:792bc097-c9d0-4f7e-8c2c-cd738e298262";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:10:"anomaly100";s:5:"links";a:3:{i:0;O:8:"stdClass":3:{s:4:"date";i:1323032017;s:4:"href";s:56:"https://twitter.com/statuses/user_timeline/191596283.rss";s:11:"description";s:11:"Submissions";}i:1;O:8:"stdClass":3:{s:4:"date";i:1323032017;s:4:"href";s:32:"http://twitter.com/#!/Anomaly100";s:11:"description";s:7:"Twitter";}i:2;O:8:"stdClass":3:{s:4:"date";i:1323032017;s:4:"href";s:25:"http://FreakOutNation.com";s:11:"description";s:14:"FreakOutNation";}}s:10:"registered";i:1206555155;s:12:"profileviews";i:0;s:8:"fullname";s:16:"I am Troy Davis ";s:4:"icon";s:56:"http://cdn3.diggstatic.com/user/3104494/l.3280862698.png";}}i:4;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:9:"Lifestyle";s:10:"short_name";s:9:"lifestyle";}s:11:"description";s:161:"Buy santa cool art prints by sini??a (sine) berstov??ek (sinonim) at Imagekind.com. Shop Thousands of Canvas and Framed Wall Art Prints and Posters at Imagekind.";s:5:"title";s:119:"santa cool Art Prints by sini??a (sine) berstov??ek (sinonim) - Shop Canvas and Framed Wall Art Prints at Imagekind.com";s:11:"submit_date";i:1323022761;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:0;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:150:"http://cdn2.diggstatic.com/story/santa_cool_art_prints_by_sini_a_sine_berstov_ek_sinonim_shop_canvas_and_framed_wall_art_prints_at_imagekind_com/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:9:"Lifestyle";s:10:"short_name";s:9:"lifestyle";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:142:"http://digg.com/news/lifestyle/santa_cool_art_prints_by_sini_a_sine_berstov_ek_sinonim_shop_canvas_and_framed_wall_art_prints_at_imagekind_com";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:81:"http://www.imagekind.com/santa-cool-art?IMID=057b713f-b60a-4b61-9336-a1896b70e8a5";s:4:"href";s:142:"http://digg.com/news/lifestyle/santa_cool_art_prints_by_sini_a_sine_berstov_ek_sinonim_shop_canvas_and_framed_wall_art_prints_at_imagekind_com";s:2:"id";s:51:"20111204181921:0b37f746-4a95-447a-befd-d53564799819";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:14:"rinaldojackomo";s:5:"links";a:0:{}s:10:"registered";i:1302851756;s:12:"profileviews";i:0;s:8:"fullname";s:12:"Sine Sinonim";s:4:"icon";s:100:"http://cdn3.diggstatic.com/user/20110415071556:25bbe5bf-93c4-4f4b-87c0-5caace3fd84b/l.2702709117.png";}}i:5;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:9:"Lifestyle";s:10:"short_name";s:9:"lifestyle";}s:11:"description";s:19:"London Modern Dream";s:5:"title";s:19:"London Modern Dream";s:11:"submit_date";i:1323022146;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:1;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:58:"http://cdn3.diggstatic.com/story/london_modern_dream/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:9:"Lifestyle";s:10:"short_name";s:9:"lifestyle";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:50:"http://digg.com/news/lifestyle/london_modern_dream";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:55:"http://www.flickr.com/photos/davidgutierrez/6452142275/";s:4:"href";s:50:"http://digg.com/news/lifestyle/london_modern_dream";s:2:"id";s:51:"20111204180906:8f35c80a-7967-4c86-9a8a-ad0850a96be7";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:12:"bilbaorocker";s:5:"links";a:4:{i:0;O:8:"stdClass":3:{s:4:"date";i:1323032017;s:4:"href";s:32:"http://www.davidgutierrez.co.uk/";s:11:"description";s:27:"David Gutierrez Photography";}i:1;O:8:"stdClass":3:{s:4:"date";i:1323032017;s:4:"href";s:44:"http://www.flickr.com/photos/davidgutierrez/";s:11:"description";s:6:"Flickr";}i:2;O:8:"stdClass":3:{s:4:"date";i:1323032017;s:4:"href";s:44:"http://davidgutierrezphotography.weebly.com/";s:11:"description";s:10:"My Website";}i:3;O:8:"stdClass":3:{s:4:"date";i:1323032017;s:4:"href";s:43:"http://www.saatchionline.com/davidgutierrez";s:11:"description";s:13:"SaatchiOnline";}}s:10:"registered";i:1307799485;s:12:"profileviews";i:0;s:8:"fullname";s:15:"David Gutierrez";s:4:"icon";s:100:"http://cdn2.diggstatic.com/user/20110611133806:a6c8872e-d515-4479-8555-c9ada4023216/l.4059842689.png";}}i:6;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:11:"description";s:0:"";s:5:"title";s:18:"Arribada 1 - FOCUS";s:11:"submit_date";i:1323021616;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:0;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:55:"http://cdn1.diggstatic.com/story/arribada_1_focus/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:51:"http://digg.com/news/entertainment/arribada_1_focus";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:58:"http://focuss.ning.com/photo/arribada-1?xg_source=activity";s:4:"href";s:51:"http://digg.com/news/entertainment/arribada_1_focus";s:2:"id";s:51:"20111204180016:1cfbe013-943e-43a4-b05b-7606b0c47654";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:7:"mnlmrn1";s:5:"links";a:0:{}s:10:"registered";i:1323021339;s:12:"profileviews";i:0;s:8:"fullname";s:13:"Manuel Moreno";s:4:"icon";s:99:"http://cdn1.diggstatic.com/user/20111204175539:64ba4a4a-eecd-4ba6-a3cc-0759d18237c5/l.908736788.png";}}i:7;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:9:"Lifestyle";s:10:"short_name";s:9:"lifestyle";}s:11:"description";s:275:"Flickr is almost certainly the best online photo management and sharing application in the world. Show off your favorite photos and videos to the world, securely and privately show content to your friends and family, or blog the photos and videos you take with a cameraphone.";s:5:"title";s:46:"anyidea1's favorite photos and videos | Flickr";s:11:"submit_date";i:1323031128;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:0;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:83:"http://cdn1.diggstatic.com/story/anyidea1_s_favorite_photos_and_videos_flickr/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:9:"Lifestyle";s:10:"short_name";s:9:"lifestyle";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:75:"http://digg.com/news/lifestyle/anyidea1_s_favorite_photos_and_videos_flickr";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:47:"http://www.flickr.com/photos/anyidea/favorites/";s:4:"href";s:75:"http://digg.com/news/lifestyle/anyidea1_s_favorite_photos_and_videos_flickr";s:2:"id";s:51:"20111204203848:1e403530-d78b-4ef2-8905-f3eaa63104b7";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:7:"anyidea";s:5:"links";a:0:{}s:10:"registered";i:1284607013;s:12:"profileviews";i:0;s:8:"fullname";s:13:"Peter Malcolm";s:4:"icon";s:100:"http://cdn3.diggstatic.com/user/20100916031653:8ccc6cd8-43f3-47f7-b10c-bc7dfb9bb33c/l.1014885050.png";}}i:8;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:11:"description";s:0:"";s:5:"title";s:16:"la noria - FOCUS";s:11:"submit_date";i:1323027012;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:0;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:53:"http://cdn2.diggstatic.com/story/la_noria_focus/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:13:"Entertainment";s:10:"short_name";s:13:"entertainment";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:49:"http://digg.com/news/entertainment/la_noria_focus";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:56:"http://focuss.ning.com/photo/la-noria?xg_source=activity";s:4:"href";s:49:"http://digg.com/news/entertainment/la_noria_focus";s:2:"id";s:51:"20111204193012:956d0a6f-2fb5-4eaf-a52f-6386f04de674";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:7:"mnlmrn1";s:5:"links";a:0:{}s:10:"registered";i:1323021339;s:12:"profileviews";i:0;s:8:"fullname";s:13:"Manuel Moreno";s:4:"icon";s:99:"http://cdn1.diggstatic.com/user/20111204175539:64ba4a4a-eecd-4ba6-a3cc-0759d18237c5/l.908736788.png";}}i:9;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:8:"Business";s:10:"short_name";s:8:"business";}s:11:"description";s:471:"zu bestellen unter: www.englisch-woerterbuch-mechatronik.de oder: www.amazon.de/exec/obidos/ASIN/3000238719 Beispiel zur Eingabe eines FACHBEGRIFFS mechatronik (schriftliche ?bersetzung): -Adresscodeformat soll ?bersetzt werden und wird vom Programm vorgegeben. -address format wird vom Lernenden eingetippt und mit der Maus abgesendet. -address format wird vom Programm als FALSCH angezeigt. AUSWERTUNG: Wegen falscher Eingabe wurde noch kein LERNFORTSCHRITT 1 erreicht.";s:5:"title";s:66:"Bild zu Adressformat soll uebersetzt werden (Begriffe Mechatronik)";s:11:"submit_date";i:1323027130;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:0;s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:103:"http://cdn1.diggstatic.com/story/bild_zu_adressformat_soll_uebersetzt_werden_begriffe_mechatronik/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:8:"Business";s:10:"short_name";s:8:"business";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:94:"http://digg.com/news/business/bild_zu_adressformat_soll_uebersetzt_werden_begriffe_mechatronik";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:53:"http://www.flickr.com/photos/69824166@N05/6451519561/";s:4:"href";s:94:"http://digg.com/news/business/bild_zu_adressformat_soll_uebersetzt_werden_begriffe_mechatronik";s:2:"id";s:51:"20111204193210:d9e17bea-961a-4cc3-970d-5068bbf40872";s:4:"user";O:8:"stdClass":6:{s:4:"name";s:9:"wagner111";s:5:"links";a:0:{}s:10:"registered";i:1192616980;s:12:"profileviews";i:0;s:8:"fullname";s:0:"";s:4:"icon";s:41:"http://cdn3.diggstatic.com/img/user/l.png";}}}
          DATA;
          $this->data = unserialize($data);
          self::$fetchedCounter = 10;
          return true;
         */

        $digg = new ImagepushDigg();
        $digg->setVersion('2.0');

        try {
            $response = $digg->search->search(array(
                'media' => 'images',
                'domain' => '*',
                'sort' => 'date-desc', //'promote_date-asc',
                'min_date' => time() - 6000,
                'count' => $this->fetchLimit
                ));
        } catch (\Services_Digg2_Exception $e) {
            return array("message" => $e->getMessage(), "code" => $e->getCode());
        }

        //\D::dump($digg->getLastResponse()->getHeader());

        if (empty($response->count)) {
            self::$fetchedCounter = 0;
            $this->data = false;
        } else {
            self::$fetchedCounter = $response->count;
            $this->data = $response->stories;
//      echo "<pre>";
//      echo serialize($response->stories);
//      echo "</pre>";
        }

        return true;
    }

    /**
     * Check and save
     * 
     * @return boolean
     * @throws \Exception 
     */
    public function checkAndSaveData()
    {

        if (!isset($this->data) || $this->data == false) {
            return false;
        }

        //$images = $this->kernel->getContainer()->get('imagepush.images');
        //$tags = $this->kernel->getContainer()->get('imagepush.tags');

        foreach ($this->data as $item) {

            if (!$this->isWorthToSave($item)) {
                continue;
            }

            $image = new Image();
            $image->setSourceType("digg");
            $image->setLink($item->link);
            // @codingStandardsIgnoreStart
            $image->setTimestamp((int) $item->submit_date);
            // @codingStandardsIgnoreEnd
            $image->setTitle($item->title);
            $image->setSlug(CustomStrings::slugify($item->title));

            if (!empty($item->topic->name)) {
                $image->setSourceTags((array) $item->topic->name);
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

                self::$savedCounter++;
            } catch (\Exception $e) {
                $this->logger->err(sprintf("Link: %s has not been saved. Error was: %s", $item->link, $e->getMessage()));
            }

            if (!empty($image->getTimestamp()->sec) && $image->getTimestamp()->sec > $this->recentSourceDate) {
                $this->recentSourceDate = $image->getTimestamp()->sec;
            }
        }
    }

    /**
     * @return type 
     */
    public function run()
    {

        // Get latest timestamp
        $image = $this->dm->getRepository('ImagepushBundle:Image')
            ->createQueryBuilder()
            ->field('sourceType')->equals("digg")
            ->sort('timestamp', 'DESC')
            ->getQuery()
            ->getSingleResult();

        //\D::dump($image);
        //\D::dump($image->getTimestamp()->sec);

        if ($image instanceof Image && time() < $image->getTimestamp()->sec + self::$minDelay) {
            self::$output[] = sprintf("[DiggFetcher] %s: Last access attempt was OK, so wait %d secords between requests (%d sec to wait).", date(DATE_RSS), self::$minDelay, $image->getTimestamp()->sec + self::$minDelay - time());
        } else {

            $status = $this->fetchData();
            //\D::dump($this->data);
            //die();

            if ($status === true) {

                $this->checkAndSaveData();

                if (self::$savedCounter == 0) {
                    self::$output[] = sprintf("[DiggFetcher] %s: %d sources received, but nothing has been saved (all filtered out).", date(DATE_RSS), self::$fetchedCounter);
                } else {
                    self::$output[] = sprintf("[DiggFetcher] %s: %d of %d items has been saved. Recent source date was on %s", date(DATE_RSS), self::$savedCounter, self::$fetchedCounter, date(DATE_RSS, $this->recentSourceDate));
                }
            } else {
                self::$output[] = sprintf("[DiggFetcher] %s: Digg replied with error: %s. Code: %s", date(DATE_RSS), $status["message"], $status["code"]);
            }
        }

        return self::$output;
    }

    /**
     * @return serialized data to use as test fixtures
     */
    /* private function serializeDataAsFixtures()
      {
      return serialize($this->data);
      } */
}
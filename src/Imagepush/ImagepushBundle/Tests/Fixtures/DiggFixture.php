<?php

namespace Imagepush\ImagepushBundle\Tests\Fixtures;

class DiggFixture {
  
  /*
   * Get data object as it was fetched from Digg
   */
  public static function getData() {
    $data = <<<EOF
a:2:{i:0;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:7:"Offbeat";s:10:"short_name";s:7:"offbeat";}s:11:"description";s:38:"That's how you make every meal healthy";s:5:"title";s:42:"McDonald's + Multivitamin =A Complete Meal";s:11:"submit_date";i:1312711579;s:5:"media";i:2;s:5:"diggs";i:1;s:8:"comments";i:0;s:2:"id";s:51:"20110807100619:00a3562b-80eb-46be-9f33-73ffdd209590";s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:7:"Offbeat";s:10:"short_name";s:7:"offbeat";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:68:"http://digg.com/news/offbeat/mcdonald_s_multivitamin_a_complete_meal";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:22:"http://imgur.com/ugp33";s:4:"href";s:68:"http://digg.com/news/offbeat/mcdonald_s_multivitamin_a_complete_meal";s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:78:"http://cdn2.diggstatic.com/story/mcdonald_s_multivitamin_a_complete_meal/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:4:"user";O:8:"stdClass":6:{s:4:"name";s:11:"birdsheaven";s:5:"links";a:0:{}s:10:"registered";i:1219569732;s:12:"profileviews";i:0;s:8:"fullname";s:11:"Felix Kiner";s:4:"icon";s:41:"http://cdn3.diggstatic.com/img/user/l.png";}}i:1;O:8:"stdClass":16:{s:6:"status";s:8:"upcoming";s:9:"container";O:8:"stdClass":2:{s:4:"name";s:7:"Offbeat";s:10:"short_name";s:7:"offbeat";}s:11:"description";s:116:"The tower goes haywire. Stolen from http://www.watchonepiecepoint.com/2010/04/stunning-manipulation-photos-of-paris/";s:5:"title";s:34:".....And Eiffel Tower Goes Wild...";s:11:"submit_date";i:1312714518;s:5:"media";i:2;s:5:"diggs";i:8;s:8:"comments";i:0;s:2:"id";s:51:"20110807105518:41e63fa6-d33a-4792-83c7-de2ed9820a84";s:5:"topic";O:8:"stdClass":2:{s:4:"name";s:7:"Offbeat";s:10:"short_name";s:7:"offbeat";}s:8:"shorturl";O:8:"stdClass":2:{s:9:"short_url";s:55:"http://digg.com/news/offbeat/and_eiffel_tower_goes_wild";s:10:"view_count";i:0;}s:12:"promote_date";N;s:4:"link";s:28:"http://i.imgur.com/3ICCK.jpg";s:4:"href";s:55:"http://digg.com/news/offbeat/and_eiffel_tower_goes_wild";s:9:"thumbnail";O:8:"stdClass":6:{s:3:"src";s:65:"http://cdn3.diggstatic.com/story/and_eiffel_tower_goes_wild/t.png";s:11:"contentType";s:9:"image/png";s:14:"originalheight";i:300;s:6:"height";i:62;s:5:"width";i:62;s:13:"originalwidth";i:300;}s:4:"user";O:8:"stdClass":6:{s:4:"name";s:6:"oteque";s:5:"links";a:0:{}s:10:"registered";i:1236308780;s:12:"profileviews";i:0;s:8:"fullname";s:18:"Hello Beloved Digg";s:4:"icon";s:45:"http://cdn3.diggstatic.com/user/4822359/l.png";}}}
EOF;

    return unserialize($data);
  }
}

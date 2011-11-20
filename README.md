Imagepush.to using Symfony2 (stable).

Rewrite from symfony 1.4 started 28th of July.

---
Redis notes:

Symfony 1.4 used Redis 2.0.4, but Symfony 2 is using Redis 2.2.12 (to upgrade to 2.4) via this bundle (https://github.com/snc/SncRedisBundle)

Upgrade Redis (http://redis.io/download):

curl -O http://redis.googlecode.com/files/redis-2.2.12.tar.gz
tar xzf redis-2.2.12.tar.gz
cd redis-2.2.12
make

---
Install PEAR Digg Services:
pear install -a -f -B pear.php.net/Services_Digg2-0.3.2
pear install -a -f pear.php.net/HTTP_Request2-0.6.0
pear install -a -f -B pear.php.net/HTTP_OAuth-0.1.18

Install PEAR Phpunit:
pear channel-discover pear.phpunit.de
pear channel-discover components.ez.no
pear channel-discover pear.symfony-project.com
pear install phpunit/PHPUnit
---
ImageMagick:
1) Install ImageMagick via MacPorts (don't use Mac OS X Binary Release from official ImageMagick)
2) download stable imagick pecl sources (like 3.0.1)
3) ./configure --with-imagick=/opt/local
4) make
5) make install
6) Edit /Applications/MAMP/Library/bin/envvars as described here to make it to use correct version of modules - http://mikepuchol.com/2010/08/26/getting-mamp-1-9-to-work-with-image-magick-imagick-so-and-other-flora/

---
Helpful about DI:
http://www.martinsikora.com/symfony2-and-dependency-injection

In general:
http://miller.limethinking.co.uk/2011/06/14/symfony2-moving-away-from-the-base-controller/

---
from deps:
[goutte]
    git=https://github.com/igorw/Goutte.git
//    git=https://github.com/fabpot/Goutte.git

        /**
         * @todo: split $uselessTags into $whitelistedTags and $blacklistedTags for each source and global. Some tags are irrelevant to show on the site, but very good to use as twitter hashtags.
         */
---
todo on production:
1) Remove null tag from redis:
del tag_d41d8cd98f00b204e9800998ecf8427e
---
Static file load test on Apache/Mac:

ab -n 1000 -c 200 -v 4 -r http://dev-anton.imagepush.to/uploads/m/2/24/247/75.jpg

Apache/Mac:
Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0  237 105.2    260    1072
Processing:     0   76 238.6      2    1069
Waiting:        0   18  62.4      0     278
Total:          1  313 187.8    272    1339

Nginx/Mac:
ab -n 1000 -c 200 -v 4 -r http://localhost/imagepush2/web/uploads/m/2/24/247/75.jpg

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        6   41  14.6     37     104
Processing:     0    6  18.0      0      87
Waiting:        0    6   8.4      6      50
Total:          6   47  22.0     38     115

---

1) Link added to link_list_to_process
2) Link moved to upcoming_image_list after Processor->run() (sorted list of images with thumbs, but without tags and scores yet)
3) TagProcessor->run() find tags for upcoming image and push to image_list - sorted list of images with add data (thumbs, tags, score) needed to show on the site.
//4) Later: ScoreProcessor->run() calculate score for the image (periodically).
5) Push from image_list based on score (later), but for now (just latest).

Todo:
rename to - Fetcher, Processor, Publisher

---
https://github.com/Nek-/FeedBundle - feed generator to try instead of Zend
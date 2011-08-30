Imagepush.to using Symfony2 (stable).

Rewrite from symfony 1.4 started 28th of July.

---
Redis notes:

Symfony 1.4 used Redis 2.0.4, but Symfony 2 is using Redis 2.2.12 via this bundle (https://github.com/snc/SncRedisBundle)

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
1) Ссылки на картинки и на сайты беруться с digg и сохраняются в модели "DiggSource"
2) "DiggSource" парсится периодически и публикуется в Images.

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


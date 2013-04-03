Imagepush.to using Symfony2 (stable).

Rewrite from symfony 1.4 started 28th of July, 2011.

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
1) Install ImageMagick via MacPorts (don't use Mac OS X Binary Release from official ImageMagick):
sudo port install ImageMagick
2) Install pecl module with location /opt/local
pecl install imagick
3) Edit /Applications/MAMP/Library/bin/envvars as described here to make it to use correct version of modules - http://thoomtech.com/post/8832473042/mamp-imagemagick-lion

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

---
Export from mongo and upload to S3:
cd ~/
mongodump --db imagepush_dev -u root -p PASSWORD_HERE
tar -cpz dump/ -f dump.tar.gz
s3cmd put dump.tar.gz s3://i.imagepush.to/dump/

Import on localhost:
cd ~/tmp
curl -O http://i.imagepush.to/dump/dump.tar.gz
tar xvfz dump.tar.gz
mongorestore --db imagepush_dev -u root -p root37 dump/imagepush_prod/
rm -rf ~/tmp/dum*

---
0) save largest version of the image as retrieved from original source and remove aWidth-like fields.
export LC_CTYPE="en_US.UTF-8"
1) put uploads/a into i.imagepush.to/i :
s3cmd sync --recursive -f -P -p -M -H --progress /var/www/imagepush/current/web/uploads/a/ s3://i.imagepush.to/i/
2) copy already created thumbs into s3:
s3cmd cp --recursive -f -P -p -M -H --progress s3://i.imagepush.to/i/ s3://i.imagepush.to/in/625x2090/i/
s3cmd sync --recursive -f -P -p -M -H --progress /var/www/imagepush/current/web/uploads/thumb/ s3://i.imagepush.to/out/140x140/i/
s3cmd sync --recursive -f -P -p -M -H --progress /var/www/imagepush/current/web/uploads/m/ s3://i.imagepush.to/in/463x1548/i/

---

* @todo: see here:
* http://sharedcount.com/?url=http%3A%2F%2Fimagepush.to%2F
* http://www.linkedin.com/cws/share-count?url=http://www.facebook.com
* 

---
Useful Varnish commands:

List urls which miss:
varnishtop -i txurl

Activity by IP:
varnishlog -b -m TxHeader:88.88.35.99

curl -I -X PURGE http://imagepush.to/
curl -I -X PURGE http://imagepush.to/about

---
Install munin:
http://www.slideshare.net/kimlindholm/varnish-configuration-step-by-step
Page 19
wget https://raw.github.com/munin-monitoring/contrib/master/plugins/varnish/varnish_cachehitratio
wget https://raw.github.com/munin-monitoring/contrib/master/plugins/varnish/varnish_healthy_backends
wget https://raw.github.com/munin-monitoring/contrib/master/plugins/varnish/varnish_hitrate
wget https://raw.github.com/munin-monitoring/contrib/master/plugins/varnish/varnish_total_objects
---
How to decide if image is a porn/nsfw ?
1) Filter by domain name, where image was found.
2) As of 21.07.2012 there is no "search by image" API comand in Google Custom Search, but it is possible to do this:
2a) Search by image URL like this:
https://www.google.no/searchbyimage?image_url=http://1.bp.blogspot.com/-MhfcaPKvuaI/T5UsS_prLUI/AAAAAAAAAOA/vLLhbC5Pnu0/s1600/pamela-anderson.jpg
2b) Get list of domains in "Pages that include matching images"
2c) Decide on good/bad domain name ratio.
3) Twitter hash tags for this link
4) HTML source page and check stop keywords in it

---
Add RabbitMQ plugins:
sudo rabbitmq-plugins enable rabbitmq_management rabbitmq_jsonrpc rabbitmq_jsonrpc_channel rabbitmq_jsonrpc_channel_examples rabbitmq_management_visualiser

Admin: http://localhost:55672/

---

Note (6.10.2012):
Using https://github.com/StartupLabs/php-amqplib instead of https://github.com/videlalvaro/php-amqplib in composer.json,
because this fix has not been merged to main master yet (https://github.com/videlalvaro/php-amqplib/issues/25).
Use original master when it will include this fix!

Use supervisord to restart rabbitmq consumers:
http://sonata-project.org/bundles/notification/master/doc/reference/command_line.html

---
1) Fetch new from reddit

2) Is porn (link1). Queue:
Find in google?
Find in twitter?
Find badwords in html?

3) Queue:
Find tag (link1) => reddit
Find tag (link1) => digg
Find tag (link1) => twitter
Find mentions (link1) => facebook

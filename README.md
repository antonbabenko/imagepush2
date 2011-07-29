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
Helpful about DI:
http://www.martinsikora.com/symfony2-and-dependency-injection

In general:
http://miller.limethinking.co.uk/2011/06/14/symfony2-moving-away-from-the-base-controller/
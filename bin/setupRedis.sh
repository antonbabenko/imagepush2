#!/bin/sh

# Install redis

mkdir -p /tmp/redis_tmp_dir
cd /tmp/redis_tmp_dir
curl -O http://redis.googlecode.com/files/redis-2.4.11.tar.gz
tar xzf redis-2.4.11.tar.gz
cd redis-2.4.11
make

cp redis.conf ~/bin/redis.conf

cd src/
cp redis-benchmark ~/bin/
cp redis-check-aof ~/bin/
cp redis-check-dump ~/bin/
cp redis-cli ~/bin/
cp redis-server ~/bin/

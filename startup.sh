#!/bin/sh

# Production server:
#sudo nice /var/redis/bin/redis-server /var/redis/bin/redis.conf
#sudo /etc/init.d/node_service.sh start

# Development server:
# export NODE_PATH="/usr/local/lib/node:/usr/local/npm/lib"
# export MANPATH=/usr/local/npm/share/man:$MANPATH
nice redis-server /Users/Bob/bin/redis.conf && node /Users/Bob/Sites/imagepush/pubsub_bin/pubsub.js

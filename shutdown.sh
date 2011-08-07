#!/bin/sh

# Use redis-cli and shutdown to not lose the data.
# This command may corrupt data:
# kill -9 `ps -A | grep redis-server | grep -v grep | cut -d " " -f 1`

sudo /etc/init.d/node_service.sh stop
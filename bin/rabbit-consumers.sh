#!/bin/bash
echo TEXT="app/console rabbitmq:consumer find_tags_and_mentions"

#PHP_BIN=`/usr/bin/env php`
NB_TASKS=4
echo NB_LAUNCHED=$(ps aux | grep "$TEXT" | grep -v grep | wc -l)
echo TASK="app/console rabbitmq:consumer find_tags_and_mentions --env=dev --messages=5"

for (( i=${NB_LAUNCHED}; i<${NB_TASKS}; i++ ))
do
    echo "$(date +%c) - Launching a new consumer"
    nohup $TASK &
done

####
# All consumers:

# Dispatcher
./app/console rabbitmq:consumer -m 10 find_tags_and_mentions

# Service consumers
./app/console rabbitmq:consumer -m 10 twitter
./app/console rabbitmq:consumer -m 10 delicious
./app/console rabbitmq:consumer -m 10 digg
...
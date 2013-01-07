#!/bin/bash

NB_TASKSc  c = 2
SYMFONY_ENV = "dev"

TEXT[0]="app/console rabbitmq:consumer primary"
TEXT[1]="app/console rabbitmq:consumer twitter"
TEXT[2]="app/console rabbitmq:consumer reddit"
TEXT[3]="app/console rabbitmq:consumer stumble_upon"
TEXT[4]="app/console rabbitmq:consumer delicious"
TEXT[5]="app/console rabbitmq:consumer source"

for text in "${TEXT[@]}"
do
    echo "$text"

NB_LAUNCHED=$(ps x | grep "$text" | grep -v grep | wc -l)

TASK="/usr/bin/env php ${text} --env=${SYMFONY_ENV} --messages=5"

for (( i=${NB_LAUNCHED}; i<${NB_TASKS}; i++ ))
do
    echo "$(date +%c) - Launching a new consumer"
    nohup $TASK &
done

done


####
# All consumers:

# Dispatcher
#./app/console rabbitmq:consumer -m 10 find_tags_and_mentions

# Service consumers
#./app/console rabbitmq:consumer -m 10 twitter
#./app/console rabbitmq:consumer -m 10 delicious
#./app/console rabbitmq:consumer -m 10 digg
#...
#!/bin/bash

NB_TASKS=1
SYMFONY_ENV="prod"

TEXT[0]="app/console rabbitmq:consumer primary"
TEXT[1]="app/console rabbitmq:consumer reddit"
TEXT[2]="app/console rabbitmq:consumer twitter"
TEXT[3]="app/console rabbitmq:consumer stumble_upon"
TEXT[4]="app/console rabbitmq:consumer delicious"
TEXT[5]="app/console rabbitmq:consumer source"

for text in "${TEXT[@]}"
do
    #echo "$text"

NB_LAUNCHED=$(ps x | grep "$text" | grep -v grep | wc -l)

# Running without --messages parameter means that it will work forever, which is good for production
TASK="/usr/bin/env php ${text} --env=${SYMFONY_ENV}"
# --messages=2

for (( i=${NB_LAUNCHED}; i<${NB_TASKS}; i++ ))
do
    echo "$(date +%c) - Launching a new consumer"
    nohup $TASK &
done

done


####
# All consumers:

# Dispatcher
#./app/console rabbitmq:consumer primary

# Service consumers
#./app/console rabbitmq:consumer -m 10 twitter
#./app/console rabbitmq:consumer -m 10 delicious
#./app/console rabbitmq:consumer -m 10 digg
#...
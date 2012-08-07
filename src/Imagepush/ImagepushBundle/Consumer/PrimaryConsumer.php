<?php

namespace Imagepush\ImagepushBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Imagepush\ImagepushBundle\Consumer\MessageTask;

class PrimaryConsumer implements ConsumerInterface
{

    public function __construct($container, $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function execute(AMQPMessage $msg)
    {
        $this->logger->crit("!!!!!!!!!!!! ===> " . $msg->body);

        $message = $this->container->get('imagepush.consumer_message')->setAMQPMessage($msg);

        if (!$message->task) {
            $this->logger->err('Task is missing. Skip message.');

            return true;
        }

        // Get array of associated producers
        $producers = (array) MessageTask::$producers[$message->task];

        if (!count($producers)) {
            $this->logger->err(sprintf('Task %s doesn\'t have producer. Most tasks should have associated producers. Skip message.', $message->task));

            return true;
        }

        if ($message->task == MessageTask::FIND_TAGS_AND_MENTIONS) {
            if (empty($message->body['image_id'])) {
                $this->logger->err('Image id is missing. Skip message.');

                return true;
            }
        }

        $this->logger->info(sprintf('Publish message %s to producers (%s)', $msg->body, implode(", ", $producers)));

        $msgCopy = clone $msg;

        foreach ($producers as $name) {
            $this->logger->crit($name . "0 ===> " . $msg->body);

            $this->logger->crit('old_sound_rabbit_mq.' . $name . '_producer');

            $producer = $this->container->get('old_sound_rabbit_mq.' . $name . '_producer', ContainerInterface::NULL_ON_INVALID_REFERENCE);

            $this->logger->crit(get_class($producer));

            if (null !== $producer) {
                $this->logger->crit($name . "1 ===> " . $msg->body);

                $producer->publish($msgCopy->body);
                $this->logger->crit($name . "2 ===> " . $msg->body);
            } else {
                $this->logger->crit(sprintf('Producer %s does not exist. Skip message.', $name));

                continue;
            }
        }

        return "DONE";

        //return true;
    }

}
<?php
//
//namespace Imagepush\ImagepushBundle\Consumer;
//
//use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
//use PhpAmqpLib\Message\AMQPMessage;
//use Symfony\Component\DependencyInjection\ContainerInterface;
//use Imagepush\ImagepushBundle\Consumer\MessageTask;
//
//class PrimaryConsumer implements ConsumerInterface
//{
//
//    public function __construct($container, $logger)
//    {
//        $this->container = $container;
//        $this->logger = $logger;
//    }
//
//    /**
//     * Consume message and republish it to other service-related queues depending on task in message.
//     *
//     * @param  AMQPMessage $msg
//     * @return boolean
//     */
//    public function execute(AMQPMessage $msg)
//    {
//        $message = $this->container->get('imagepush.consumer_message')->setAMQPMessage($msg);
//
//        if (!$message->task) {
//            $this->logger->err('Task is missing. Skip message.');
//
//            return true;
//        } elseif (empty(MessageTask::$producers[$message->task])) {
//            $this->logger->err(sprintf('Task %s is not defined. Skip message.', $message->task));
//
//            return true;
//        }
//
//        // Get array of associated producers
//        $producers = (array) MessageTask::$producers[$message->task];
//
//        if (!count($producers)) {
//            $this->logger->err(sprintf('Task %s doesn\'t have producer. Most tasks should have associated producers. Skip message.', $message->task));
//
//            return true;
//        }
//
//        // Validate parameters
//        if ($message->task == MessageTask::FIND_TAGS_AND_MENTIONS) {
//            if (empty($message->body['image_id'])) {
//                $this->logger->crit('Image id is missing. Skip message.');
//
//                return true;
//            }
//        }
//
//        $this->logger->info(sprintf('Publish message %s to producers (%s)', $msg->body, implode(", ", $producers)));
//
//        foreach ($producers as $name) {
//
//            if (null !== $producer = $this->container->get('old_sound_rabbit_mq.' . $name . '_producer', ContainerInterface::NULL_ON_INVALID_REFERENCE)) {
//                $producer->publish($msg->body);
//            } else {
//                $this->logger->crit(sprintf('Producer %s does not exist. Skip message.', $name));
//
//                continue;
//            }
//        }
//
//        return true;
//    }
//
//}

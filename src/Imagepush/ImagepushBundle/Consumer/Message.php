<?php

namespace Imagepush\ImagepushBundle\Consumer;

use PhpAmqpLib\Message\AMQPMessage;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

/**
 * Useful functions to be used inside Consumers classes
 * 
 */
class Message
{

    /**
     * Message body
     * 
     * @var array
     */
    public $body;

    /**
     * Desired action task
     * 
     * @var string
     */
    public $task;

    /**
     *
     * @var type Performed attempts for this message
     */
    public $attempts;

    /**
     * @param ContainerInterface $container
     * @param \Monolog\Logger    $logger
     */
    public function __construct($container, $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->dm = $container->get('doctrine.odm.mongodb.document_manager');
    }

    /**
     * Returns decoded message body
     * 
     * @param \PhpAmqpLib\Message\AMQPMessage $msg
     * 
     * @return array
     */
    public function setAMQPMessage(AMQPMessage $msg)
    {
        $this->body = json_decode($msg->body, true);
        $this->logger->debug($msg->body);

        $this->task = (empty($this->body["task"]) ? null : $this->body["task"]);

        $this->attempts = $this->body["attempts"] = (empty($this->body["attempts"]) ? 0 : $this->body["attempts"]);

        return $this;
    }

    /**
     * Get from body
     * 
     * @return boolean
     */
    public function getImageId()
    {

        $image = $this->dm
            ->getRepository('ImagepushBundle:Image')
            ->findOneBy(array("id" => $this->body['image_id']));

        if (!$image) {
            $this->logger->err('Image id ' . $this->body['image_id'] . ' does not exist.');

            return true;
        }

        return $this->body['image_id'];
    }

    /**
     * @param \OldSound\RabbitMqBundle\RabbitMq\Producer $producer
     */
    public function publishToRetry(Producer $producer)
    {
        $this->attempts = $this->body["attempts"] = $this->body["attempts"] + 1;

        $producer->publish(json_encode($this->body));
    }

}
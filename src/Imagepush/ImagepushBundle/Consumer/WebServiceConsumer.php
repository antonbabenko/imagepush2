<?php

namespace Imagepush\ImagepushBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Monolog\Logger;
use Imagepush\ImagepushBundle\Consumer\MessageTask;
use Imagepush\ImagepushBundle\Services\Processor\ProcessorStatusCode;
use Imagepush\ImagepushBundle\Services\AccessControl\ServiceAccess;

/**
 * General web-service consumer class, which contains logic for all message consumers
 */
class WebServiceConsumer implements ConsumerInterface
{

    public function __construct($container, $serviceKey)
    {
        $this->container = $container;

        $this->producer = $container->get('old_sound_rabbit_mq.' . $serviceKey . '_producer', ContainerInterface::NULL_ON_INVALID_REFERENCE);

        $this->service = $container->get('imagepush.access_control.service')->setKey($serviceKey);
    }

    public function setLogger($logger = null)
    {
        $this->logger = $logger;
    }

    public function log($level, $message)
    {
        $message = sprintf('%s: %s', $this->service->key, $message);

        $this->logger->addRecord($level, $message);
    }

    public function execute(AMQPMessage $msg)
    {

        $message = $this->container->get('imagepush.consumer_message')->setAMQPMessage($msg);

        if ($message->attempts >= $this->service->maxAttempts) {
            $this->log(Logger::INFO, "Message has exceeded attempts. Skip message.");

            return true;
        }

        // Extract required parameters
        if ($message->task == MessageTask::FIND_TAGS_AND_MENTIONS) {
            if (true === $imageId = $message->getImageId()) {
                return true;
            }
        }

        // Should service be accessed now or sleep?
        $delay = $this->service->getDelay();
        if ($delay > 0) {
            $this->log(Logger::INFO, sprintf("Service %s should be accessed in %d seconds. Sleeping.", $this->service->key, $delay));
            sleep($delay);
        }

        // Always: update last access timestamp for this service before actual call if exception will not be caught.
        // To prevent from accessing service too often!
        $this->service->updateLastAccess();

        // Get result from task specific action
        if ($message->task == MessageTask::FIND_TAGS_AND_MENTIONS) {
            //$result = $this->container->get("imagepush.processor.tag." . $this->key)->find($imageId);
            $result = array("tag_1", rand());
        } else {
            $this->log(Logger::CRITICAL, sprintf("Unknown message task: %s", $message->task));

            return true;
        }

        // OK
        if (is_array($result)) {
            $statusCode = 0;

            //$this->service->saveTags($result);
        } else {
            $statusCode = $result;
        }

        /**
         * Status code - is result from function.
         * 0 - OK. Processed.
         * 1 - Retry the same request in service specific interval (normal).
         * 2 - Don't retry the same request. It was an error. Skip it.
         * 3 - Don't retry this service at all now. Service is down. Retry when service is back.
         */
        switch ($statusCode):
            case ProcessorStatusCode::OK:
                $this->log(Logger::DEBUG, 'Status=0 -- OK! There were tags found! ' . print_r(json_encode($result), true));
                break;

            case ProcessorStatusCode::RETRY_REQUEST_AGAIN:
                if ($this->producer) {
                    $message->publishToRetry($this->producer);
                    $this->log(Logger::DEBUG, 'Status=1 -- Republish: ' . print_r(json_encode($message->body), true));
                } else {
                    $this->log(Logger::CRITICAL, 'Status=1 -- No service defined');
                }
                break;

            case ProcessorStatusCode::WRONG_REQUEST:
                $this->log(Logger::DEBUG, 'Status=2 -- Don\'t retry the same request. Skip it.');
                break;

            case ProcessorStatusCode::SERVICE_IS_DOWN:
                $this->service->updateServiceStatus(ServiceAccess::STATUS_FAIL);
                $this->log(Logger::DEBUG, 'Status=3 -- Service is currently down. Try later. Body: ' . print_r(json_encode($message->body), true));

                // And requeue the message to process when service is back.
                return false;

            default:
                $this->log(Logger::ERROR, "UNKNOWN STATUS CODE " . print_r($statusCode, true) . ". Message skiped");
                break;

        endswitch;

        // Set service status to "Available"
        $this->service->updateServiceStatus(ServiceAccess::STATUS_OK);

        return true;
    }

}
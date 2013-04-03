<?php

namespace Imagepush\ImagepushBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Monolog\Logger;
use LogicException;
use Imagepush\ImagepushBundle\Consumer\MessageTask;
use Imagepush\ImagepushBundle\Services\Processor\ProcessorStatusCode;
use Imagepush\ImagepushBundle\Services\AccessControl\ServiceAccess;

/**
 * General web-service consumer class, which contains logic for all message consumers
 */
class WebServiceConsumer implements ConsumerInterface
{
    /**
     * @var Imagepush\ImagepushBundle\Service\AccessControl\ServiceAccess
     */
    public $service;

    /**
     * @param ContainerInterface $container
     * @param string             $serviceKey
     */
    public function __construct(ContainerInterface $container, $serviceKey)
    {
        if (empty($serviceKey)) {
            throw new LogicException("serviceKey is not defined");
        }

        $this->container = $container;

        $this->producer = $container->get('old_sound_rabbit_mq.' . $serviceKey . '_producer', ContainerInterface::NULL_ON_INVALID_REFERENCE);

        $this->service = $container->get('imagepush.access_control.service')->setKey($serviceKey);

        $this->dm = $container->get('doctrine.odm.mongodb.document_manager');

        $this->serviceKey = $serviceKey;
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

    /**
     * @param  AMQPMessage $msg
     * @return boolean
     */
    public function execute(AMQPMessage $msg)
    {

        $statusCode = ProcessorStatusCode::OK;

        /* @var $message Imagepush\ImagepushBundle\Consumer\Message */
        $message = $this->container->get('imagepush.consumer_message')->setAMQPMessage($msg);

        if ($message->attempts >= $this->service->maxAttempts) {
            $this->log(Logger::INFO, "Message has exceeded attempts. Skip message.");

            return true;
        }

        $this->log(Logger::INFO, "Message: image_id=".$message->body['image_id']);

        // Validate parameters
        if ($message->task == MessageTask::FIND_TAGS_AND_MENTIONS) {

            $image = $this->dm
                ->getRepository('ImagepushBundle:Image')
                ->findOneBy(array("id" => $message->body['image_id']));

            if (!$image) {
                $this->log(Logger::INFO, sprintf("Image id %d does not exist.", $message->body['image_id']));

                return true;
            }

            $imageId = $message->body['image_id'];
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

            $processor = $this->container->get("imagepush.processor.tag." . $this->serviceKey, ContainerInterface::NULL_ON_INVALID_REFERENCE);

            if (null === $processor) {
                $this->log(Logger::CRITICAL, sprintf("Unknown processor tag service: %s", "imagepush.processor.tag." . $this->serviceKey));

                return true;
            } else {

                try {

                    $result = $processor->find($image);
                    //$result = array("tag_1", rand());

                    $this->log(Logger::INFO, "TAGS=".json_encode($result));

                    if (is_array($result)) {
                        $statusCode = ProcessorStatusCode::OK;

                        $processor->saveTagsFound($imageId, $this->serviceKey, $result);
                    } else {
                        $statusCode = $result;
                    }
                } catch (\Exception $e) {
                    $this->log(Logger::CRITICAL, sprintf("Exception caught. Code %s. Message: %s", $e->getMessage(), $e->getCode()));
                }
            }
        } else {
            $this->log(Logger::CRITICAL, sprintf("Unknown message task: %s", $message->task));

            return true;
        }

        /**
         * Status code - is result from function.
         * 0 - OK. Processed.
         * 1 - Retry the same request in service specific interval (normal).
         * 2 - Don't retry the same request. It was an error. Skip it.
         * 3 - Don't retry this service at all now. Service is down. Retry when service is back.
         */
        if (ProcessorStatusCode::OK == $statusCode) {
            $serviceOk = true;
            $this->log(Logger::DEBUG, 'Status=0 -- OK! There were tags found! ' . print_r(json_encode($result), true));
        } elseif (ProcessorStatusCode::RETRY_REQUEST_AGAIN == $statusCode) {
            $serviceOk = true;
            if (null !== $this->producer) {
                $message->publishToRetry($this->producer);
                $this->log(Logger::DEBUG, 'Status=1 -- Republish: ' . print_r(json_encode($message->body), true));
            } else {
                $this->log(Logger::CRITICAL, 'Status=1 -- No service defined');
            }
        } elseif (ProcessorStatusCode::WRONG_REQUEST == $statusCode) {
            $serviceOk = true;
            $this->log(Logger::DEBUG, 'Status=2 -- Don\'t retry the same request. Skip it.');
        } elseif (ProcessorStatusCode::SERVICE_IS_DOWN == $statusCode) {
            $serviceOk = false;
            $this->log(Logger::DEBUG, 'Status=3 -- Service is currently down. Try later. Body: ' . print_r(json_encode($message->body), true));

            // And requeue the message to process when service is back.
            //return false;
        } else {
            $serviceOk = true;
            $this->log(Logger::ERROR, "UNKNOWN STATUS CODE " . print_r($statusCode, true) . ". Message skiped");
        }

        // Update service status
        $this->service->updateServiceStatus($serviceOk ? ServiceAccess::STATUS_OK : ServiceAccess::STATUS_FAIL);

        return true;
    }

}

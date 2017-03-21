<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Repository\ImageRepository;
use Imagepush\ImagepushBundle\Services\AccessControl\ServiceAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processor tags
 */
class ProcessorTag
{

    /**
     * @var ContainerInterface $container
     */
    public $container;

    /**
     * @var \Aws\Sqs\SqsClient
     */
    protected $sqs;

    /**
     * @var $imageRepo ImageRepository
     */
    protected $imageRepo;

    protected $sqsQueueUrlFindTags;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get('imagepush.processor_logger');

        $this->sqs = $container->get('aws.sqs');
        $this->sqsQueueUrlFindTags = $container->getParameter('imagepush.sqs_queue_url_find_tags');

        $this->imageRepo = $container->get('imagepush.repository.image');
    }

    public function processTag()
    {

        $result = false;

        $request = [
            'MaxNumberOfMessages' => 1,
            'QueueUrl' => $this->sqsQueueUrlFindTags,
        ];
        $messages = $this->sqs->receiveMessage($request);

        $messages = $messages->get('Messages');

        if (0 == count($messages)) {
            $log = "Ok, but there is no unprocessed images to work on...";
            $this->logger->info($log);

            return $log;
        }

        $this->sqs->deleteMessage([
            'QueueUrl' => $this->sqsQueueUrlFindTags,
            'ReceiptHandle' => $messages[0]['ReceiptHandle']
        ]);

        $this->logger->info(sprintf('SQS message: %s', $messages[0]['Body']));
        $body = json_decode($messages[0]['Body'], true);
        $id = $body['id'];
        $serviceKey = $body['service'];

        ////////////////// from WebServiceConsumer
        $service = $this->container->get('imagepush.access_control.service')->setKey($serviceKey);
        $processor = $this->container->get("imagepush.processor.tag." . $serviceKey, ContainerInterface::NULL_ON_INVALID_REFERENCE);

        if (null === $processor) {
            $this->logger->crit(sprintf("Unknown processor tag service: %s", "imagepush.processor.tag." . $serviceKey));

            return true;
        }

        $statusCode = ProcessorStatusCode::OK;

        $this->logger->info("Message: image_id=".$id);

        $image = $this->imageRepo->findOneBy($id, false);

        if (!$image) {
            $this->logger->info(sprintf("ID: %d. Image was no found in DB", $id));

            return true;
        }

        // Should service be accessed now or sleep?
        $delay = $service->getDelay();
        if ($delay > 0) {
            $this->logger->info(sprintf("Service %s should be accessed in %d seconds. Sleeping.", $service->key, $delay));
            sleep($delay);
        }

        // Always: update last access timestamp for this service before actual call if exception will not be caught.
        // To prevent from accessing service too often!
        $service->updateLastAccess();

        // Get result from task specific action

        try {
            $result = $processor->find($image);
//            $result = json_decode('{"world news":1,"worldnewshub":1,"news":1,"chapotraphouse":1,"law":1,"technology":1,"newsofthestupid":1,"todayilearned":1}', true);

            $this->logger->info("Found tags: " . json_encode($result));

            if (is_array($result)) {
                $statusCode = ProcessorStatusCode::OK;

                if (count($result)) {
                    $processor->saveTagsFound($image, $serviceKey, $result);
                }

            } else {
                $statusCode = $result;
            }
        } catch (\Exception $e) {
            $this->logger->crit(sprintf("Exception code %s. Message: %s", $e->getCode(), $e->getMessage()));
        }

        /**
         * Status code - is result from function.
         * 0 - OK. Processed.
         * 2 - Don't retry the same request. It was an error. Skip it.
         * 3 - Don't retry this service at all now. Service is down. Retry when service is back.
         */
        if (ProcessorStatusCode::OK == $statusCode) {
            $serviceOk = true;
            $this->logger->debug('Status=0 -- OK! ' . print_r(json_encode($result), true));
        } elseif (ProcessorStatusCode::WRONG_REQUEST == $statusCode) {
            $serviceOk = true;
            $this->logger->debug('Status=2 -- Don\'t retry the same request. Skip it.');
        } elseif (ProcessorStatusCode::SERVICE_IS_DOWN == $statusCode) {
            $serviceOk = false;
            $this->logger->debug('Status=3 -- Service is currently down. Try later. Body: ' . print_r(json_encode($result), true));
        } else {
            $serviceOk = true;
            $this->logger->err("UNKNOWN STATUS CODE " . print_r($statusCode, true) . ". Message skipped.");
        }

        // Update service status
        $service->updateServiceStatus($serviceOk ? ServiceAccess::STATUS_OK : ServiceAccess::STATUS_FAIL);

        return true;
    }

}

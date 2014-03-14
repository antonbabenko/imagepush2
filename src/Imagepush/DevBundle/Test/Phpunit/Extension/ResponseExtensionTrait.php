<?php
namespace Imagepush\DevBundle\Test\Phpunit\Extension;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

trait ResponseExtensionTrait
{
    /**
     * @param Response $response
     */
    public function assertResponseStatusRedirection($response)
    {
        /** @var $response Response */

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertGreaterThanOrEqual(300, $response->getStatusCode(), $this->getMessage($response));
        $this->assertLessThan(400, $response->getStatusCode(), $this->getMessage($response));
    }

    /**
     * @param string   $response
     * @param Response $expectedUrl
     */
    public function assertResponseRedirection($response, $expectedUrl)
    {
        /** @var $response Response */

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertResponseStatusRedirection($response);
        $this->assertEquals($expectedUrl, $response->headers->get('Location'));
    }

    /**
     * @param string $expectedUrl
     */
    public function assertClientResponseRedirection($expectedUrl)
    {
        $this->assertResponseRedirection(static::$client->getResponse(), $expectedUrl);
    }

    /**
     * @param Response $response
     * @param string   $expectedUrl
     */
    public function assertResponseRedirectionStartsWith($response, $expectedUrl)
    {
        /** @var $response Response */

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertResponseStatusRedirection($response);
        $this->assertStringStartsWith($expectedUrl, $response->headers->get('Location'));
    }

    /**
     * @param string $expectedUrl
     */
    public function assertClientResponseRedirectionStartsWith($expectedUrl)
    {
        $this->assertResponseRedirectionStartsWith(static::$client->getResponse(), $expectedUrl);
    }

    /**
     * @param Response $response
     * @param int      $expectedStatus
     */
    public function assertResponseStatus($response, $expectedStatus)
    {
        /** @var $response Response */

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals($expectedStatus, $response->getStatusCode(), $this->getMessage($response));
    }

    /**
     * @param int $expectedStatus
     */
    public function assertClientResponseStatus($expectedStatus)
    {
        $this->assertResponseStatus(static::$client->getResponse(), $expectedStatus);
    }

    /**
     * @param Response $response
     */
    public function assertResponseContentHtml($response)
    {
        /** @var $response Response */

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals('text/html; charset=UTF-8', $response->headers->get('Content-Type'), $this->getMessage($response));
    }

    public function assertClientResponseContentHtml()
    {
        $this->assertResponseContentHtml(static::$client->getResponse());
    }

    public function assertClientResponseContentJson()
    {
        $this->assertResponseContentJson(static::$client->getResponse());
    }

    /**
     * @param Response $response
     */
    public function assertResponseContentJson($response)
    {
        /** @var $response Response */

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertNotNull(
            $this->getClientResponseJsonContent($response),
            "Failed to decode content. The content is not valid json: \n\n".$response->getContent()
        );
    }

    /**
     * @param Response $response
     *
     * @return mixed
     */
    public function getResponseJsonContent($response)
    {
        /** @var $response Response */

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);

        return json_decode($response->getContent());
    }

    /**
     * @return mixed
     */
    public function getClientResponseJsonContent()
    {
        return $this->getResponseJsonContent(static::$client->getResponse());
    }

    /**
     * @param Response $response
     *
     * @return string
     */
    private function getMessage(Response $response)
    {
        if (500 >= $response->getStatusCode() && $response->getStatusCode() < 600) {
            $crawler = new Crawler();
            $crawler->addHtmlContent($response->getContent());

            if ($crawler->filter('.text-exception h1')->count() > 0) {
                $exceptionMessage = trim($crawler->filter('.text-exception h1')->text());

                $trace = '';
                if ($crawler->filter('#traces-0 li')->count() > 0) {
                    list($trace) = explode("\n", trim($crawler->filter('#traces-0 li')->text()));
                }

                return $message = 'Internal Server Error: '.$exceptionMessage.' '.$trace;
            }
        }

        return $response->getContent();
    }
}

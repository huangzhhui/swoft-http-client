<?php

namespace Swoft\HttpClient;

use Psr\Http\Message\ResponseInterface;
use Swoft\Core\AbstractDataResult;
use Swoft\HttpClient\Adapter\ResponseTrait;
use Swoft\Http\Message\Stream\SwooleStream;
use Swoft\HttpClient\Exception\RuntimeException;
use Swoft\Http\Message\Base\Response;

/**
 * Http Result
 */
class HttpResult extends AbstractDataResult implements HttpResultInterface
{
    use ResponseTrait;

    /**
     * @var resource
     */
    public $client;

    /**
     * @var bool
     */
    protected $receive = false;

    /**
     * @var Response
     */
    protected $response;

    /**
     * Return result
     *
     * @param array $params
     * @return string
     * @throws \Swoft\HttpClient\Exception\RuntimeException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getResult(...$params): string
    {
        $response = $this->getResponse(...$params);
        return $response->getBody()->getContents();
    }

    /**
     * @alias getResult()
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Swoft\HttpClient\Exception\RuntimeException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getResponse(...$params): ResponseInterface
    {
        if (! $this->isReceive()) {
            $client = $this->client;
            if (!\is_resource($client)) {
                throw new RuntimeException('Supplied resource is not a valid cURL handler resource');
            }

            $status = curl_getinfo($client, CURLINFO_HTTP_CODE);
            $headers = curl_getinfo($client);
            curl_close($client);
            $this->setReceive(true);
            $this->response = $this->createResponse()
                ->withBody(new SwooleStream($this->data ?? ''))
                ->withStatus($status)
                ->withHeaders($headers ?? []);
        }
        return $this->response;
    }

    /**
     * @return bool
     */
    public function isReceive(): bool
    {
        return $this->receive;
    }

    /**
     * @param bool $receive
     * @return HttpResult
     */
    public function setReceive($receive)
    {
        $this->receive = $receive;
        return $this;
    }

}

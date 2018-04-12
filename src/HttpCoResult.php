<?php

namespace Swoft\HttpClient;

use Psr\Http\Message\ResponseInterface;
use Swoft\Core\AbstractCoResult;
use Swoft\HttpClient\Adapter\ResponseTrait;
use Swoft\Http\Message\Stream\SwooleStream;


/**
 * Http Defer Result
 *
 * @property \Swoole\Http\Client|resource $connection
 */
class HttpCoResult extends AbstractCoResult implements HttpResultInterface
{

    use ResponseTrait;

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
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getResponse(...$params): ResponseInterface
    {
        if (!$this->isReceive()) {
            $client = $this->connection;
            $this->recv();
            $result = $client->body;
            $client->close();
            $this->setReceive(true);
            $headers = value(function () {
                $headers = [];
                foreach ($this->connection->headers as $key => $value) {
                    $exploded = explode('-', $key);
                    foreach ($exploded as &$str) {
                        $str = ucfirst($str);
                    }
                    $ucKey = implode('-', $exploded);
                    $headers[$ucKey] = $value;
                }
                unset($str);
                return $headers;
            });
            $this->response = $this->createResponse()
                ->withBody(new SwooleStream($result ?? ''))
                ->withHeaders($headers ?? [])
                ->withStatus($this->deduceStatusCode($client));
        }
        return $this->response;
    }

    /**
     * Transfer sockets error code to HTTP status code.
     * TODO transfer more error code
     *
     * @param \Swoole\HttpClient $client
     * @return int
     */
    private function deduceStatusCode($client): int
    {
        if ($client->errCode === 110) {
            $status = 404;
        } else {
            $status = $client->statusCode;
        }
        return $status > 0 ? $status : 500;
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
     * @return HttpCoResult
     */
    public function setReceive($receive)
    {
        $this->receive = $receive;
        return $this;
    }

}
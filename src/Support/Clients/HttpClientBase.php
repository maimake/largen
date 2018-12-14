<?php

namespace Maimake\Largen\Support\Clients;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Illuminate\Auth\AuthenticationException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class HttpClientBase
 * @package Maimake\Largen\Support\Clients
 *
 * @method head($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 * @method get($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 * @method post($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 * @method put($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 * @method patch($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 * @method delete($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 * @method purge($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 * @method options($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 * @method trace($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 * @method connect($path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
 */
abstract class HttpClientBase
{
    protected $logger;
    protected $httpClient;


    protected $jar;
    protected $useCookie;


    private $check_login = true;


    public function __construct(bool $useCookie, array $config = [], LoggerInterface $logger = null, $logFormat = '{code} "{method} {uri}" {res_header_Content-Length}')
    {
        $this->useCookie = $useCookie;

        if (empty($config['handler']))
        {
            $config['handler'] = HandlerStack::create();
        }

        $stack = $config['handler'];


        // NOTICE: The middlewares are in a stack. FILO

        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $this->modifyRequest($request);
        }));


        if ($logger)
        {
            $this->logger = $logger;
            $stack->push(
                Middleware::log(
                    $this->logger,
                    new MessageFormatter($logFormat)
                )
            );
        }


        $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {
            return $this->modifyResponse($response);
        }));

        $this->httpClient = new Client($config);
    }

    protected function getCookie()
    {
        if (!$this->useCookie) return null;
        if (!$this->jar) $this->jar = new CookieJar();
        return $this->jar;
    }

    /************
     *  Request
     *************/


    protected function _apiCall($httpMethod, $path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
    {
        $option = [
            RequestOptions::QUERY => $query,
            RequestOptions::HEADERS => $headers,
            RequestOptions::COOKIES => $this->getCookie(),
            $dataType => $data,
        ];

        $response = $this->httpClient->request($httpMethod, $path, $option);
        $response_data = $this->getBodyFromResponse($response);
        return [$response, $response_data];
    }

    abstract protected function isSuccess(ResponseInterface $response, $response_data);
    abstract protected function getErrorMsg(ResponseInterface $response, $response_data);

    protected function transformBody(ResponseInterface $response, $body) {
        if (array_first($response->getHeader('Content-Type')) == 'application/json')
        {
            return \json_decode($body, true);
        }
        else {
            return $body;
        }
    }

    protected function getBodyFromResponse(?ResponseInterface $response)
    {
        if (is_null($response)) return null;

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        return $this->transformBody($response, $body);
    }

    protected function modifyRequest(RequestInterface $request)
    {
        return $request;
    }

    protected function modifyResponse(ResponseInterface $response)
    {
        return $response;
    }

    /************
     *  Login
     *************/

    abstract protected function isLogined();
    abstract protected function login();

    protected function requireLogin()
    {
        if ($this->check_login)
        {
            $this->check_login = false;

            if (!$this->isLogined())
                $this->login();

            if (!$this->isLogined())
                throw new AuthenticationException('You are not allowed to request the resource');

            $this->check_login = true;
        }
    }


    /************
     *  Simple request methods
     *************/


    function __call($method, $arguments)
    {
        if (in_array($method, ['head', 'get', 'post', 'put', 'patch', 'delete', 'purge', 'options', 'trace', 'connect']))
        {
            array_unshift($arguments, strtoupper($method));
            $method = method_exists($this, 'apiCall') ? 'apiCall' : '_apiCall';
            return call_user_func_array([$this, $method], $arguments);
        }
    }
}

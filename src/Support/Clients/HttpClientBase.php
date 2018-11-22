<?php

namespace Maimake\Largen\Support\Clients;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Illuminate\Auth\Access\AuthorizationException;
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


    public function __construct(bool $useCookie, array $config = [], LoggerInterface $logger = null, $logFormat = '{code} "{method} {uri}" {res_header_Content-Length}')
    {
        $this->useCookie = $useCookie;
        $this->jar = new CookieJar();


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
                    new MessageFormatter(config('app.debug', false) ? MessageFormatter::DEBUG : $logFormat)
                )
            );
        }


        $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {
            return $this->modifyResponse($response);
        }));

        $this->httpClient = new Client($config);
    }


    /************
     *  Request
     *************/


    protected function _apiCall($httpMethod, $path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
    {
        $option = [
            RequestOptions::QUERY => $query,
            RequestOptions::HEADERS => $headers,
            RequestOptions::COOKIES => $this->useCookie ? $this->jar : null,
            $dataType => $data,
        ];

        $response = $this->httpClient->request($httpMethod, $path, $option);
        $response->getBody()->rewind();
        $response_data = $response->getBody()->getContents();
        return [$response, $response_data];
    }

    abstract protected function isSuccess(Response $response, $response_data);
    abstract protected function getErrorMsg(Response $response, $response_data);


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
        if (!$this->isLogined())
            $this->login();

        throw_unless($this->isLogined(), AuthorizationException::class, 'You are not allowed to request the resource');
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

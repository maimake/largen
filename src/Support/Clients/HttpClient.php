<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2018/12/1
 * Time: 00:01
 */

namespace Maimake\Largen\Support\Clients;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Maimake\Largen\Support\Exceptions\HttpClientLogicException;
use Psr\Http\Message\ResponseInterface;

class HttpClient extends HttpClientBase
{
    protected function apiCall($httpMethod, $path, $query = [], $data = [], $dataType = RequestOptions::JSON, $headers = [])
    {
        try{
            list($response, $body) = $this->_apiCall($httpMethod, $path, $query, $data, $dataType, $headers);

            if(!$this->isSuccess($response, $body))
            {
                $body = $this->handleLogicException($response, $body);
            }
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if (is_null($response)) {
                $body = null;
                $this->handleClientException($e, null, null);
            } else {
                $body = $this->getBodyFromResponse($response);

                $e_class = get_class($e);
                $new_e = $e_class::create($e->getRequest(), $e->getResponse(), $e->getPrevious(), ['body' => $body]);
                $body = $this->handleClientException($new_e, $response, $body);
            }
        }

        return $body;
    }


    protected function isSuccess(ResponseInterface $response, $response_data)
    {
        return $response_data['code'] == 0;
    }

    protected function getErrorMsg(ResponseInterface $response, $response_data)
    {
        return $response_data['message'];
    }

    protected function handleClientException(RequestException $e, ?ResponseInterface $response, $body)
    {
        throw $e;
    }

    protected function handleLogicException(ResponseInterface $response, $body)
    {
        throw new HttpClientLogicException($this->getErrorMsg($response, $body), $response->getStatusCode(), $body);
    }

    /************
     *  Login
     *************/
    protected function isLogined()
    {
        return true;
    }

    protected function login()
    {

    }
}

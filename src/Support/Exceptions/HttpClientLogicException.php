<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2017/12/19
 * Time: 16:18
 */

namespace Maimake\Largen\Support\Exceptions;

class HttpClientLogicException extends \Exception
{
    public $body;

    public function __construct($message = "", $code = 0, $body = null)
    {
        parent::__construct($message, $code);
        $this->body = $body;
    }

}

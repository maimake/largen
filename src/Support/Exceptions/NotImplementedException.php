<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2017/12/19
 * Time: 16:18
 */

namespace Maimake\Largen\Support\Exceptions;

class NotImplementedException extends \Exception
{
    protected $message = "Method is not implemented";
}
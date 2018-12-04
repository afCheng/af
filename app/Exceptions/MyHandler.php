<?php

namespace App\Exceptions;

class MyHandler extends \Dingo\Api\Exception\Handler
{
    public function genericResponse(\Exception $exception)
    {
        $res = parent::genericResponse($exception);
        $res->setStatusCode(200);
        return $res;
    }
}
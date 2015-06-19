<?php
namespace Services\HelloSwoole;

use Services\HelloSwoole\Message;

class Handler implements HelloSwooleIf
{
    public function sendMessage(Message $msg)
    {
        var_dump($msg);
        return RetCode::PARAM_ERROR;
    }
}
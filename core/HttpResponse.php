<?php
namespace Bro\core;


class HttpResponse
{
    private $responseText = '';
    private $responseCode = 200;

    public function make($code, $text)
    {
        $this->responseCode = $code;
        $this->responseText = $text;
    }

    public function makeJsonStatus($code, $text)
    {
        $this->responseCode = $code;
        $this->responseText = json_encode(array('status' => $text));
    }

    public function sendResponse()
    {
        header('HTTP/1.0 ' . $this->responseCode);
        echo($this->responseText);
    }
}
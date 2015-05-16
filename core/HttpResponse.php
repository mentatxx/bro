<?php
/**
 * Created by PhpStorm.
 * User: Mentat
 * Date: 07.06.2014
 * Time: 10:50
 */

namespace Bro\core;


class HttpResponse {
    private $responseText = '';
    private $responseCode = 200;

    public function make($code, $text) {
        $this->responseCode = $code;
        $this->responseText = $text;
    }

    public function sendResponse() {
        header('HTTP/1.0 '.$this->responseCode);
        echo($this->responseText);
    }
}
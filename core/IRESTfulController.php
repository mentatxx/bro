<?php
namespace Bro\core;


interface IRESTfulController
{
    public function requestGet(HttpResponse $response, $id);

    public function requestPut(HttpResponse $response, $id, $value);

    public function requestPost(HttpResponse $response, $id, $value);

    public function requestDelete(HttpResponse $response, $id);
}
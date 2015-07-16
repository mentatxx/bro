<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 16.07.2015
 * Time: 10:02
 */

namespace Bro\core;


interface IRESTfulController
{
    public function requestGet(HttpResponse $response, $id);
    public function requestPut(HttpResponse $response, $id);
    public function requestPost(HttpResponse $response, $id);
    public function requestDelete(HttpResponse $response, $id);
}
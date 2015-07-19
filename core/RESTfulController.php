<?php
namespace Bro\core;


class RESTfulController implements IRESTfulController
{
    /**
     * @var UrlRouter
     */
    var $router;
    var $path;
    var $postfixGet = '/(.+)';
    var $postfixPut = '/(.+)';
    var $postfixPost = '/(.+)';
    var $postfixDelete = '/(.+)';

    public function __construct(UrlRouter $router, $path)
    {
        $this->router = $router;
        $this->path = $path;
    }

    public function route($q, HttpResponse $response)
    {
        $this->router->route($q, '#^' . $this->path . $this->postfixGet . '$#', array($this, 'requestGet'), $response, 'GET');
        $this->router->route($q, '#^' . $this->path . $this->postfixPut . '$#', array($this, 'requestPut'), $response, 'PUT');
        $this->router->route($q, '#^' . $this->path . $this->postfixPost . '$#', array($this, 'requestPost'), $response, 'POST');
        $this->router->route($q, '#^' . $this->path . $this->postfixDelete . '$#', array($this, 'requestDelete'), $response, 'DELETE');
    }

    public function requestGet(HttpResponse $response, $id)
    {
        $response->make('405', 'Not implemented');
    }

    public function requestPut(HttpResponse $response, $id, $value)
    {
        $response->make('405', 'Not implemented');
    }

    public function requestPost(HttpResponse $response, $id, $value)
    {
        $response->make('405', 'Not implemented');
    }

    public function requestDelete(HttpResponse $response, $id)
    {
        $response->make('405', 'Not implemented');
    }
}
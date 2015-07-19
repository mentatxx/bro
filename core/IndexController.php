<?php
namespace Bro\core;


class IndexController
{
    /**
     * @var UrlRouter
     */
    var $router;
    var $path;

    public function __construct(UrlRouter $router, $path)
    {
        $this->router = $router;
        $this->path = $path;
    }

    public function route($q, HttpResponse $response)
    {
        $this->router->route($q, '#^' . $this->path . '/(\d+)/(\d+)$#', array($this, 'index'), $response);
        $this->router->route($q, '#^' . $this->path . '/count$#', array($this, 'count'), $response);
    }

    public function index(HttpResponse $response, $offset, $limit)
    {
        $response->make('405', 'Not implemented');
    }

    public function count(HttpResponse $response)
    {
        $response->make('405', 'Not implemented');
    }
}
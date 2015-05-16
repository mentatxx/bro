<?php
namespace Bro\core;


abstract class PageExtension implements IModuleExtension
{
    static public function register($name)
    {
        Modules::registerPageController($name, get_called_class());
    }
    abstract public function call($parameters);
}

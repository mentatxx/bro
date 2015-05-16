<?php
namespace Bro\core;

abstract class ModuleExtension implements IModuleExtension
{
    static public function register($name)
    {
        Modules::registerModuleController($name, get_called_class());
    }
    abstract public function call($parameters);
}



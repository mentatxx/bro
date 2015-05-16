<?php
namespace Bro\core;


/**
 *
 * Abstract classes for controller registration
 *
 */

interface IModuleExtension
{
    static public function register($name);
    public function call($parameters);
}

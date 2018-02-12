<?php
namespace core;
defined('ENVIRONMENT') OR exit('No direct script access allowed');

class ClassLoader
{
    public static function loader($className)
    {
        $className = str_replace('\\', '/', $className);
        $class_file = './' . $className . ".php";
        if (file_exists($class_file)) {
            require_once($class_file);
        } else {
            echo $class_file . ' not found' . PHP_EOL;
            exit;
        }
    }
}

<?php
namespace listener;
defined('ENVIRONMENT') OR exit('No direct script access allowed');


interface Listener
{
    function getData();

    function setup(...$para);
}
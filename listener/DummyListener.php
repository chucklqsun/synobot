<?php
namespace listener;
defined('ENVIRONMENT') OR exit('No direct script access allowed');


class DummyListener implements Listener
{
    function getData()
    {
        return null;
    }

    function setup(...$para)
    {
        return true;
    }
}
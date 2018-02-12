<?php
namespace core;
defined('ENVIRONMENT') OR exit('No direct script access allowed');


use Exception;

class Tunnel
{
    static $TIMEOUT = 10;
    private $ch;

    function __construct()
    {
        $this->init();
    }

    private function init()
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); //trust any cert
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0); //check domain, 0 means no check
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, self::$TIMEOUT);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, self::$TIMEOUT); //timeout in seconds
    }

    public function setHead($head)
    {
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $head);
    }

    public function getData($url, $proxy = "")
    {
        if ($proxy) {
            $this->setProxy($proxy);
        }
        $ret = $this->exec($url, false);
        return $ret;
    }

    private function setProxy($proxy)
    {
        curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
    }

    private function exec($url, $isPost)
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, $isPost);
        $ret = curl_exec($this->ch);

        if (curl_errno($this->ch)) {
            BotLogger::error(ERR_RESPONSE, __FILE__, __LINE__, curl_errno($this->ch) . "|" . $url);
            throw new Exception(ERR_RESPONSE);
        } else {
            BotLogger::info(OK_RESPONSE, __FILE__, __LINE__, $url);
        }

        curl_close($this->ch);
        return $ret;
    }
}
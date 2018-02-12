<?php
namespace listener;
defined('ENVIRONMENT') OR exit('No direct script access allowed');

use core\BotLogger;
use core\Tunnel;
use Exception;

class TVshowListener implements Listener
{

    static $API = "http://api.ousns.net/tv/schedule?";
    static $PROXY = "47.90.41.9:80";
    private $APIConfig;
    private $head = array(
        "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:48.0) Gecko/20100101 Firefox/48.0\r
Accept-Language: en-US,en;q=0.5\r
Content-Encoding: gzip\r
Connection: keep-alive\r
Cache-Control: max-age=0\r
Cookie: PHPSESSID=a8rpdvheh2aroemojb5g8h4tn6");


    private function getResponse()
    {
        $tunnel = new Tunnel();
        $api = DEBUG_TIMEOUT ? FAKE_API : self::$API;
        $proxy = DEBUG_TIMEOUT ? "" : self::$PROXY;
        $api = $api . http_build_query($this->APIConfig);
        $tunnel->setHead($this->head);
        $json = $tunnel->getData($api, $proxy);
        try {
            if (!$json) {
                BotLogger::error(ERR_RETURN_FORMAT, __FILE__, __LINE__, substr($json, 0, 20));
                throw new Exception(ERR_RETURN_FORMAT);
            }
        } catch (Exception $e) {
            throw $e;
        }
        return json_decode($json, true);
    }

    public function setup(...$para)
    {
        //stub
        return true;
    }

    private function fixZero($str)
    {
        return intval($str) < 10 ? "0" . intval($str) : $str;
    }

    public function getData()
    {
        $this->APIConfig = array(
            "accesskey" => "3eeffcb25583d4f5a232d6b853737b2b",
            "cid" => "5",
            "client" => "2",
            "end" => date("Y-m-d"),
            "start" => date("Y-m-d"),
            "timestamp" => "1473854828",
        );

        $data = $this->getResponse();

        $cnnames = array();
        $images = array();
        if ($data["status"] != "1") {
            BotLogger::error(ERR_RETURN_FORMAT, __FILE__, __LINE__, $data["info"]);
            throw new Exception($data["info"]);
        }
        if (isset($data["data"]["" . date("Y-m-d")])) {
            foreach ($data["data"]["" . date("Y-m-d")] as $item) {
                $cnnames[] = $item["cnname"] .
                    "S" . $this->fixZero($item["season"]) .
                    "EP" . $this->fixZero($item["episode"]);
                $images[] = $item["poster"];
            }
        }
        $rand_logo = array_rand($images, 1);

        return array(
            implode("; ", $cnnames),
            $images[$rand_logo],
        );

    }
}

<?php
namespace listener;
defined('ENVIRONMENT') OR exit('No direct script access allowed');

use core\BotLogger;
use core\Tunnel;
use Exception;

class WeatherListener implements Listener
{
    private $location;

    private $textTemp = "今日%s - %s，%s° ~ %s°，%s";
    private $textDefault = "sorry,这地方天气我不知道:)";

    static $ATTR = "@attributes";
    static $LOW_TEMP = "tem1";
    static $HIGH_TEMP = "tem2";
    static $DETAIL = "stateDetailed";
    static $WIND = "windState";
    static $API = "http://flash.weather.com.cn/wmaps/xml/china.xml";
    static $PROXY = "183.131.119.109:80";

    private function getResponse()
    {
        $tunnel = new Tunnel();
        $api = DEBUG_TIMEOUT ? FAKE_API : self::$API;
        $proxy = DEBUG_TIMEOUT ? "" : self::$PROXY;
        $xml = $tunnel->getData($api, $proxy);
        try {
            $ret = simplexml_load_string($xml);
            if (!$ret) {
                BotLogger::error(ERR_RETURN_FORMAT, __FILE__, __LINE__, substr($ret, 0, 20));
                throw new Exception(ERR_RETURN_FORMAT);
            }
        } catch (Exception $e) {
            throw $e;
        }
        return $ret;
    }

    private function reformatData($data)
    {
        $cities = (array)$data;
        $ret = array();
        foreach ($cities as $city) {
            //filter useless array
            if (is_array($city) && array_keys($city) === range(0, count($city) - 1)) {
                foreach ($city as $info) {
                    $infoArray = (array)$info;
                    $ret[$infoArray[self::$ATTR]["cityname"]] = array(
                        self::$LOW_TEMP => $infoArray[self::$ATTR][self::$LOW_TEMP],
                        self::$HIGH_TEMP => $infoArray[self::$ATTR][self::$HIGH_TEMP],
                        self::$DETAIL => $infoArray[self::$ATTR][self::$DETAIL],
                        self::$WIND => $infoArray[self::$ATTR][self::$WIND],
                    );
                }
            }
        }
        return $ret;
    }

    public function setup(...$para)
    {
        $this->location = $para[0][1];
        if ($this->location == "") {
            return false;
        }
        return true;
    }

    public function getData()
    {
        if (!HAS_NETWORK) {
            return sprintf($this->textTemp, $this->location, "多云", "19", "27", "微风");
        }

        $rawData = $this->getResponse();
        $data = $this->reformatData($rawData);
        if (!array_key_exists($this->location, $data)) {
            $weather = $this->textDefault;
        } else {
            $weather = sprintf($this->textTemp,
                $this->location,
                $data[$this->location][$this::$DETAIL],
                $data[$this->location][$this::$LOW_TEMP],
                $data[$this->location][$this::$HIGH_TEMP],
                $data[$this->location][$this::$WIND]);
        }
        return $weather;
    }
}

<?php
namespace listener;
defined('ENVIRONMENT') OR exit('No direct script access allowed');


use core\BotLogger;
use core\SynoBot;
use core\Tunnel;
use Exception;

class StockListener implements Listener
{
    private $type = array(
        "11" => "A股",
        "12" => "B股",
        "13" => "权证",
        "14" => "期货",
        "15" => "债券",
        "21" => "开基",
        "22" => "ETF",
        "23" => "LOF",
        "24" => "货基",
        "25" => "QDII",
        "26" => "封基",
        "31" => "港股",
        "32" => "窝轮",
        "41" => "美股",
        "42" => "外期",
        "82" => "--",
    );
    private $stock;
    private $textSuggest = "你是要查询哪个? %s";
    private $textTemp = '%s 分时k线';
    private $fileUrl = array(
        "11" => "http://image.sinajs.cn/newchart/min/n/%s.gif",
        "12" => "http://image.sinajs.cn/newchart/hk_stock/min/%s.gif",
        "31" => "http://image.sinajs.cn/newchart/hk_stock/min/%s.gif",
        "41" => "http://image.sinajs.cn/newchartv5/usstock/min/%s.gif"
    );
    private $textDefault = "sorry,这股票我不知道:)";
    static $SUGGEST_API = "http://suggest3.sinajs.cn/suggest/type=&name=suggestdata&key=%s";
    static $SUGGEST_PROXY = "113.108.216.234:80";

    private function getSuggestion()
    {
        $tunnel = new Tunnel();
        $api = DEBUG_TIMEOUT ? FAKE_API : sprintf(self::$SUGGEST_API, $this->stock);
        $proxy = DEBUG_TIMEOUT ? "" : self::$SUGGEST_PROXY;
        $ret = $tunnel->getData($api, $proxy);

        if (preg_match('/var suggestdata="(.*)"/', $ret, $match)) {
            $suggest = explode(";", $match[1]);
            return $suggest;
        } else {
            BotLogger::error(ERR_RETURN_FORMAT, __FILE__, __LINE__, substr($ret, 0, 20));
            throw new Exception(ERR_RETURN_FORMAT);
        }
    }

    public function setup(...$para)
    {
        $this->stock = $para[0][1];
        return true;
    }

    public function getData()
    {
        if (!HAS_NETWORK) {
            return sprintf($this->stock . ", 今开:7.27, 最高:7.30, 最低:7.26, 成交量:19.20万手 ");
        }
        $suggest = $this->getSuggestion();
        $suggestOne = array();
        if (count($suggest) > 1) {
            $str = array();
            foreach ($suggest as $item) {
                $arr = explode(",", $item);
                //check if code is stock name
                if ($arr[3] === $this->stock || $arr[2] === $this->stock) {
                    $suggestOne = array($item);
                    break;
                }
                array_push($str, iconv("gb18030", "utf-8", $arr[4]) . "[" . $this->type[$arr[1]] . "]" . "(" . $arr[3] . ")");
            }
            if (count($suggestOne) == 0) {
                return sprintf($this->textSuggest, implode(",", $str));
            }
        }
        //given the match code one
        if (count($suggestOne) == 1) {
            $suggest = $suggestOne;
        }

        if (count($suggest) == 1) {
            $arr = explode(",", $suggest[0]);
            if (count($arr) > 1 && isset($this->fileUrl[$arr[1]])) {
                //tricky way to output html
                if (SynoBot::getType() == RENDER_PAYLOAD) {
                    $link = SynoBot::getLink();
                    return $link[SynoBot::$PAY_LOAD_TEXT];
                } else {
                    SynoBot::setType(RENDER_HTML);
                }

                if ($arr[1] == "41") { //US stock
                    return array(
                        sprintf($this->textTemp, iconv("gb18030", "utf-8", $arr[4])),
                        sprintf($this->fileUrl[$arr[1]], $arr[2])
                    );
                } else {
                    return array(
                        sprintf($this->textTemp, iconv("gb18030", "utf-8", $arr[4])),
                        sprintf($this->fileUrl[$arr[1]], $arr[3])
                    );
                }
            } else {
                return $this->textDefault;
            }
        } else {
            return $this->textDefault;
        }
    }
}

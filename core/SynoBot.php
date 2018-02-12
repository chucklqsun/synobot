<?php
namespace core;
defined('ENVIRONMENT') OR exit('No direct script access allowed');


use Exception;
use listener\DummyListener;

class SynoBot
{
    static $HTML_TEMPLATE = "<html>
<head>
    <meta property='og:title' content='Powered by 机器人阿呆'>
    <meta property='og:description' content='%s'>
    <meta property='og:image' content='%s'>
</head>
<body>
</body>
</html>";
    static $PAY_LOAD_TEXT = "text";
    static $PAY_LOAD_TYPE = "type";
    static $PAY_LOAD_TOKEN = "token";

    static $PAY_LOAD_POST_ID = "post_id";
    static $PAY_LOAD_USERNAME = "username";
    static $PAY_LOAD_USER_ID = "user_id";
    static $PAY_LOAD_CHANNEL_NAME = "channel_name";
    static $PAY_LOAD_CHANNEL_ID = "channel_id";
    static $PAY_LOAD_TIMESTAMP = "timestamp";

    static $PAY_LOAD_FILE_URL = "file_url";


    static $LINK_TEMPLATE = '正在努力加载中... <http://%s?%s| >';


    static $CONFIG = array(
        "帮助" => array(
            "cmd" => "帮助",
            "class" => '\listener\HelpListener',
            "type" => RENDER_PAYLOAD,
        ),
        "天气" => array(
            "cmd" => "天气",
            "class" => '\listener\WeatherListener',
            "type" => RENDER_PAYLOAD,
        ),
        "股票" => array(
            "cmd" => "股票",
            "class" => '\listener\StockListener',
            "type" => RENDER_PAYLOAD,
        ),
        "节目" => array(
            "cmd" => "节目",
            "class" => '\listener\TVshowListener',
            "type" => RENDER_HTML,
        ),
        "投票" => array(
            "cmd" => "投票",
            "class" => '\listener\VoteListener',
            "type" => RENDER_PAYLOAD,   //maybe could use img later
        ),
    );

    private static $TYPE;
    private static $TIPS;
    private static $CUR_CMD = "";

    public static function getType()
    {
        return self::$TYPE;
    }

    public static function setType($type)
    {
        self::$TYPE = $type;
    }

    public function talk($input)
    {
        $ret = array();
        $lsn = new DummyListener();
        try {
            $match = array();
            foreach (self::$CONFIG as $key => $value) {
                $pattern = "/^!\s*(.*)\s*" . $value["cmd"] . "(.*)/";
                if (preg_match($pattern, SynoBot::getTips(), $match)) {

                    //chat callback for pure html output cmd
                    if (self::$TYPE != $value["type"] && $value["type"] == RENDER_HTML) {
                        BotLogger::info(sprintf("cmd:%s,this_type:%s,value:%s",
                            $value["cmd"], self::$TYPE, $value["type"]), __FILE__, __LINE__);
                        return $this->render(SynoBot::getLink(), RENDER_PAYLOAD);
                    }

                    unset($lsn);
                    $lsn = new $value["class"]();

                    self::$CUR_CMD = $value["cmd"];
                    break;
                }
            }
            if (self::$CUR_CMD == "") {
                exit;   //no listener match, let other robot rock!
            }

            //return false means params check failed
            if (!$lsn->setup($match, $input)) {
                $ret[self::$PAY_LOAD_TEXT] = INFO_PARAMS_MISS . self::$CUR_CMD . "?";
                return $this->render($ret, RENDER_PAYLOAD);
            }

            $result = $lsn->getData();

        } catch (Exception $e) {
            $result = $e->getMessage() . ERR_POSTFIX;
        }
        if (is_array($result)) {
            $ret[self::$PAY_LOAD_TEXT] = $result[0];
            $ret[self::$PAY_LOAD_FILE_URL] = $result[1];
        } else {
            $ret[self::$PAY_LOAD_TEXT] = $result;
        }
        return $this->render($ret, self::$TYPE);
    }

    public static function getTips()
    {
        return self::$TIPS;
    }

    public static function setTips($tips)
    {
        self::$TIPS = $tips;
    }

    private function render($data, $type)
    {
        switch ($type) {
            case RENDER_PAYLOAD:
                $ret = json_encode($data, JSON_UNESCAPED_UNICODE);
                break;
            case RENDER_HTML:
                $ret = sprintf(self::$HTML_TEMPLATE, $data["text"], isset($data["file_url"]) ? $data["file_url"] : "");
                break;
            default:
                BotLogger::error(ERR_RENDER_TYPE, __FILE__, __LINE__, "type:" . $type);
                exit;
        }
        return $ret;
    }

    public static function getLink()
    {
        $para = array(
            self::$PAY_LOAD_TEXT => SynoBot::getTips(),
            self::$PAY_LOAD_TYPE => RENDER_HTML,
            self::$PAY_LOAD_TOKEN => TOKEN,
        );
        return array(
            self::$PAY_LOAD_TEXT =>
                sprintf(self::$LINK_TEMPLATE, $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'], http_build_query($para)),
        );
    }
}


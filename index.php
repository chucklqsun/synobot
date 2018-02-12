<?php
define('ENVIRONMENT', isset($_SERVER['SYNOBOT_ENV']) ? $_SERVER['SYNOBOT_ENV'] : 'development');
use core\BotLogger;
use core\SynoBot;

require_once("config/define.php");
require_once("core/ClassLoader.php");
spl_autoload_register('\core\ClassLoader::loader');

/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */
switch (ENVIRONMENT) {
    case 'development':
        error_reporting(-1);
        ini_set('display_errors', 1);
        break;
    case 'testing':
    case 'production':
        ini_set('display_errors', 0);
        if (version_compare(PHP_VERSION, '5.3', '>=')) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        break;
    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'The application environment is not set correctly.';
        exit(1); // EXIT_ERROR
}

class App
{
    public static $INPUT;

    //main routine
    public static function run()
    {
        BotLogger::info(json_encode($_POST, JSON_UNESCAPED_UNICODE), __FILE__, __LINE__);
        self::$INPUT = self::verifyInput();

        $bot = new SynoBot();
        SynoBot::setType(self::$INPUT[SynoBot::$PAY_LOAD_TYPE]);
        SynoBot::setTips(self::$INPUT[SynoBot::$PAY_LOAD_TEXT]);
        echo $bot->talk(self::$INPUT);
    }


    //input {"token":"CX415o1FJywr","channel_id":"4","channel_name":"机器人实验室",
    //"user_id":"2","username":"admin","post_id":"17179869515",
    //"timestamp":"1473914726594","text":"!help","trigger_word":"!help"}
    private static function verifyInput()
    {
        $input = array();

        //status:
        //0:not must
        //1 chat POST must
        //2 must POST and GET
        $params = array(
            SynoBot::$PAY_LOAD_TEXT => 2,
            SynoBot::$PAY_LOAD_USER_ID => 1,
            SynoBot::$PAY_LOAD_USERNAME => 1,
            SynoBot::$PAY_LOAD_TYPE => 0,   //not a must input item
            SynoBot::$PAY_LOAD_CHANNEL_ID => 1,
            SynoBot::$PAY_LOAD_CHANNEL_NAME => 1,
            SynoBot::$PAY_LOAD_TIMESTAMP => 1,
            SynoBot::$PAY_LOAD_POST_ID => 1,
        );

        foreach ($params as $param => $isMust) {
            if ($isMust === 2 && !isset($_REQUEST[$param])) {
                BotLogger::info(ERR_PARAMS_MISS, __FILE__, __LINE__, $param);
                exit;
            } elseif ($isMust === 1 && !isset($_POST[$param]) && $_SERVER['REQUEST_METHOD'] === 'POST') {
                BotLogger::info(ERR_PARAMS_MISS, __FILE__, __LINE__, $param);
                exit;
            }
            switch ($param) {
                case SynoBot::$PAY_LOAD_TOKEN:
                    if (TOKEN !== $_REQUEST[$param]) {
                        BotLogger::info(ERR_TOKEN, __FILE__, __LINE__);
                        exit;
                    }
                    break;
                case SynoBot::$PAY_LOAD_TEXT:
                    if (!preg_match("/^!.*/", $_REQUEST[$param])) {
                        //not a SynoBot input
                        exit;
                    }
                    break;
                case SynoBot::$PAY_LOAD_TYPE:
                    $_REQUEST[$param] = isset($_REQUEST[$param]) ?
                        intval($_REQUEST[$param]) : RENDER_PAYLOAD;
                default:
            }

            $input[$param] = isset($_REQUEST[$param]) ? trim($_REQUEST[$param]) : "";
        }

        return $input;
    }
}

App::run();


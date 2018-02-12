<?php
namespace core;
defined('ENVIRONMENT') OR exit('No direct script access allowed');


class BotLogger
{
    const ERROR = "Error";
    const WARN = "Warn";
    const INFO = "Info";

    const MAX_SIZE = 64 * 1024; //64k
    const LOG_PATH = TEMP_PATH . DIRECTORY_SEPARATOR . "log.txt";

    static function error($text, $file, $line, $data = "")
    {
        self::log(self::ERROR, $text, $file, $line, $data);
    }

    private static function log($flag, $text, $file, $line, $data = "")
    {
        $str = sprintf("%s-%s-L:%s,MSG: %s |%s <br>\r\n", $flag, $file, $line, $text, $data);
        if (PRINT_LOG) {
            echo $str;
        } else {
            if (@filesize(self::LOG_PATH) > self::MAX_SIZE) {
                file_put_contents(self::LOG_PATH, $str);
            } else {
                file_put_contents(self::LOG_PATH, $str, FILE_APPEND);
            }
        }
    }

    static function warn($text, $file, $line, $data = "")
    {
        self::log(self::WARN, $text, $file, $line, $data);
    }

    static function info($text, $file, $line, $data = "")
    {
        self::log(self::INFO, $text, $file, $line, $data);
    }
}
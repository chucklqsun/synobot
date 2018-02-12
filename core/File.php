<?php
namespace core;
defined('ENVIRONMENT') OR exit('No direct script access allowed');


class File
{
    const LOCK_FILE_PREFIX = "botLock_";
    const MODE_CREATE_OPEN = "x";
    const MODE_TRUNCATE = "w";
    const DIR_MODE = 0777;
    public static $ERROR = "";

    public static function create($content, $filePath, $mode)
    {
        if ($mode == self::MODE_CREATE_OPEN && self::checkFileExist($filePath)) {
            self::$ERROR = ERR_FILE_EXIST;
            BotLogger::info(ERR_FILE_EXIST, __FILE__, __LINE__, $filePath);
            return false;
        } elseif ($mode == self::MODE_TRUNCATE && !self::checkFileExist($filePath)) {
            self::$ERROR = ERR_FILE_NOT_EXIST;
            BotLogger::info(ERR_FILE_NOT_EXIST, __FILE__, __LINE__, $filePath);
            return false;
        }

        $path_parts = pathinfo($filePath);
        $dir = $path_parts['dirname'];

        //create dir
        if (false === is_dir($dir)) {
            if (!mkdir($dir, self::DIR_MODE, true)) {
                BotLogger::error(ERR_CREATE_DIR, __FILE__, __LINE__, $dir);
            }
        }

        //create
        $f = fopen($filePath, $mode);
        if ($f && $content != "") {
            if (!flock($f, LOCK_EX | LOCK_NB)) {
                self::$ERROR = ERR_LOCK_EX;
                BotLogger::error(ERR_LOCK_EX, __FILE__, __LINE__, $filePath);
                fclose($f);
                return false;
            }

            fwrite($f, $content);
            if (!flock($f, LOCK_UN)) {
                BotLogger::error(ERR_LOCK_UN, __FILE__, __LINE__, $filePath);
            }
            fclose($f);
            return true;
        } else {
            self::$ERROR = ERR_CREATE_FILE;
            BotLogger::error(ERR_CREATE_FILE, __FILE__, __LINE__, $filePath);
            return false;
        }
    }

    private static function checkFileExist($filePath)
    {
        if (is_file($filePath)) {
            return true;
        } else {
            return false;
        }
    }

    public static function read($filePath)
    {
        if (!self::checkFileExist($filePath)) {
            self::$ERROR = ERR_FILE_NOT_EXIST;
            BotLogger::info(ERR_FILE_NOT_EXIST, __FILE__, __LINE__, $filePath);
            return false;
        }

        $f = fopen($filePath, "r");
        if ($f) {
            if (!flock($f, LOCK_SH | LOCK_NB)) {
                self::$ERROR = ERR_LOCK_EX;
                BotLogger::error(ERR_LOCK_EX, __FILE__, __LINE__, $filePath);
                fclose($f);
                return false;
            }

            $content = array();
            while (!feof($f)) {
                $line = fgets($f);
                $content[] = $line;
            }

            if (!flock($f, LOCK_UN)) {
                BotLogger::error(ERR_LOCK_UN, __FILE__, __LINE__, $filePath);
            }
            fclose($f);
            return $content;
        } else {
            self::$ERROR = ERR_READ_FILE;
            BotLogger::error(ERR_READ_FILE, __FILE__, __LINE__, $filePath);
            return false;
        }

    }

    public static function append($content, $filePath)
    {
        if (!self::checkFileExist($filePath)) {
            self::$ERROR = ERR_FILE_NOT_EXIST;
            BotLogger::info(ERR_FILE_NOT_EXIST, __FILE__, __LINE__, $filePath);
            return false;
        }

        $f = fopen($filePath, "r+");
        if ($f && $content) {
            if (!flock($f, LOCK_EX | LOCK_NB)) {
                self::$ERROR = ERR_LOCK_EX;
                BotLogger::error(ERR_LOCK_EX, __FILE__, __LINE__, $filePath);
                fclose($f);
                return false;
            }
            fseek($f, 0, SEEK_END);
            if (is_array($content)) {
                fwrite($f, implode("", $content));
            } else {
                fwrite($f, $content);
            }
            if (!flock($f, LOCK_UN)) {
                BotLogger::error(ERR_LOCK_UN, __FILE__, __LINE__, $filePath);
            }
            fclose($f);
            return true;
        } else {
            self::$ERROR = ERR_CREATE_FILE;
            BotLogger::error(ERR_CREATE_FILE, __FILE__, __LINE__, $filePath);
            return false;
        }
    }

    public static function delete($filePath)
    {
        if (!self::checkFileExist($filePath)) {
            self::$ERROR = ERR_FILE_NOT_EXIST;
            BotLogger::info(ERR_FILE_NOT_EXIST, __FILE__, __LINE__, $filePath);
            return false;
        }
        return unlink($filePath);
    }
}
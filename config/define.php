<?php
defined('ENVIRONMENT') OR exit('No direct script access allowed');
//setting
set_time_limit(10);// set 0 as infinity, not recommend

define("TOKEN", "CX415o1FJywr"); //token for verify

define("APP_PATH", getcwd());
define("TEMP_PATH", APP_PATH . DIRECTORY_SEPARATOR . "tmp");

define("PRINT_LOG", false);  //for debug only, set true to print log std
define("DEBUG_TIMEOUT", false);  //for debug only, use www.google.com

define("HAS_NETWORK", true);  //set false for no network
define("FAKE_API", "http://www.google.com");

define("OK_RESPONSE", "服务器有返回数据");
define("OK_RETURN_FORMAT", "服务器返回格式正确");

define("ERR_TOKEN", "TOKEN错误或不存在");

//file operation
define("ERR_READ_FILE", "读取文件失败");
define("ERR_CREATE_DIR", "创建文件夹失败");
define("ERR_CREATE_FILE", "创建文件失败");
define("ERR_FILE_EXIST", "文件已存在");
define("ERR_FILE_NOT_EXIST", "文件不存在");
define("ERR_LOCK_EX", "文件加排斥锁失败");
define("ERR_LOCK_UN", "文件释放锁失败");


define("ERR_POSTFIX", ",请开发哥哥帮帮忙吧");
define("ERR_RESPONSE", "服务器加载错误");
define("ERR_RETURN_FORMAT", "服务器返回格式错误");

define("ERR_RENDER_TYPE", "渲染类型错误");
define("INFO_PARAMS_MISS", "你想知道的是啥");
define("ERR_PARAMS_MISS", "缺失参数");
define("ERR_PARAMS", "错误参数");


define("RENDER_PAYLOAD", 1);
define("RENDER_HTML", 2);

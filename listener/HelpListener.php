<?php
namespace listener;
defined('ENVIRONMENT') OR exit('No direct script access allowed');


use core\SynoBot;

class HelpListener implements Listener
{
    function getData()
    {
        $title = "#助人为乐 如何与机器人阿呆交流：";
        $func = implode("|", array_keys(SynoBot::$CONFIG));
        $ret = "$title
 (1) 不要说粗话;\r
 (2) 阿呆目前知道这些事情,$func;\r
 (3) 举些栗子:(不含单引号哟)
>输入 '!节目'
>输入 '!北京天气'
>输入 '!中国石油股票'
 (4) 投票功能(同一个频道，同样名字的投票只能创建一个)
>输入 '!创建投票[团建活动|2|吃饭|看电影|唱歌]'  //2代表每人最多选两个
>输入 '!我要投票[团建活动|1|2]'    //代表我要选择 吃饭或者看电影
>输入 '!查看投票[团建活动]'
>输入 '!重置投票[团建活动|1|吃饭|看电影|唱歌]'   //按照新的配置将这个投票活动重置为0
>输入 '!删除投票[团建活动]'           //删除，不可恢复，可以重新再创建新的
 (5) 你可以联系开发哥哥 77167680 at qq.com 来获取进一步帮助;
";
        return $ret;
    }

    function setup(...$para)
    {
        //stub
        return true;
    }
}

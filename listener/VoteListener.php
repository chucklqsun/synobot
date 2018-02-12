<?php
namespace listener;
defined('ENVIRONMENT') OR exit('No direct script access allowed');


use core\BotLogger;
use core\File;
use core\SynoBot;

class VoteListener implements Listener
{

    private $curCmd;
    private $curPar;
    private $file;
    private $userId, $userName, $channelId;

    const MSG_DEFAULT = "未知操作，请参照帮助文档";

    const MSG_NOT_ALLOW_ERR = "你无权进行本操作";
    const MSG_CREATE_ERR = "创建投票失败";
    const MSG_CREATE_OK = "创建投票成功";
    const MSG_RESET_ERR = "重置投票失败";
    const MSG_RESET_OK = "重置投票成功";
    const MSG_DELETE_ERR = "删除投票失败";
    const MSG_DELETE_OK = "删除投票成功";
    const MSG_CHECK_ERR = "查看投票失败";
    const MSG_READ_ERR = "读取投票失败";

    const MSG_VOTE_NUM_ERR = "对不起，每人最多投%s票";
    const MSG_VOTE_DUPLICATE_ERR = "不能重复投票";
    const MSG_VOTE_NUM_NOT_EXIST = "这个选项不存在:";

    const  MSG_VOTE_OK = "感谢你的宝贵投票";
    const  MSG_VOTE_ERR = "不好意思，投票失败";

    const CMD_CREATE = "创建";
    const CMD_DELETE = "删除";
    const CMD_RESET = "重置";
    const CMD_CHECK = "查看";
    const CMD_VOTE = "我要";

    const ALLOW_ANYONE = 0;
    const ALLOW_OWNER = 0;

    const TEXT_VOTE_NAME_DEFAULT = "default_vote";
    const TEXT_VOTE_NAME = "vote_name";
    const TEXT_VOTE_TYPE = "vote_type";
    const TEXT_CREATE_USER_ID = "create_user_id";
    const TEXT_CREATE_USER_NAME = "create_user_name";
    const TEXT_VOTE_ITEMS = "vote_items";
    const TEXT_VOTE_ITEM_NAME = "vote_item_name";
    const TEXT_VOTE_ITEM_TOTAL = "vote_item_total";

    private $cmd = array(
        self::CMD_CREATE => self::ALLOW_ANYONE, //;anyone  but vote name is unique
        self::CMD_DELETE => self::ALLOW_OWNER,  //;owner
        self::CMD_RESET => self::ALLOW_OWNER,  //;owner
        self::CMD_CHECK => self::ALLOW_ANYONE, //;anyone  anytime
        self::CMD_VOTE => self::ALLOW_ANYONE, //;anyone  but only one shot
    );


    private $showHeadTemplate = "投票[%s] : 已经参与人数[%s]\r\n";
    private $showLineTemplate = "%s. [%s]已经有[%s]人支持\r\n";

//file: [channel_id]/[md5(vote_name)].txt
    private $headTemplate = ";create_user_id:%s
;create_user_name:%s
;create_date:%s
;" . self::TEXT_VOTE_NAME . ":%s
;" . self::TEXT_VOTE_TYPE . ":%s
;" . self::TEXT_VOTE_ITEMS . ":%s
;user_id|user_name|vote number(1,2,3...)|timestamp\r\n";
    private $lineTemplate = "%s|%s|%s|%s\r\n";

    //render vote overview
    private function renderOverview($content)
    {
        $voteName = "";
        $votePeople = 0;
        $voteItems = array();
        $n = 1;

        foreach ($content as $line) {
            $line = trim($line);
            if (preg_match("/^;" . self::TEXT_VOTE_NAME . ":(.*)/", $line, $match)) {
                $voteName = $match[1];
                unset($match);
            } elseif (preg_match("/^;" . self::TEXT_VOTE_ITEMS . ":(.*)/", $line, $match)) {
                $items = $match[1];
                foreach (explode("|", $items) as $item) {
                    $voteItems[] = array(
                        self::TEXT_VOTE_ITEM_NAME => $item,
                        self::TEXT_VOTE_ITEM_TOTAL => 0,
                    );
                }
                unset($match);
            } elseif (preg_match("/^\d+?\|/", $line)) {
                $fields = explode("|", $line);
                $votePeople++;
                foreach (explode(",", $fields[2]) as $idx) {
                    $voteItems[$idx - 1][self::TEXT_VOTE_ITEM_TOTAL]++;
                }
            }
        }

        $ret = sprintf($this->showHeadTemplate, $voteName, $votePeople);
        foreach ($voteItems as $idx => $item) {
            $ret .= sprintf($this->showLineTemplate,
                $n,
                $item[self::TEXT_VOTE_ITEM_NAME],
                $item[self::TEXT_VOTE_ITEM_TOTAL]
            );
            $n++;
        }
        return $ret;
    }

    //exec before opt file
    private function checkPrivilege()
    {
        if ($this->cmd[$this->curCmd] === self::ALLOW_ANYONE) {
            return true;
        }

        $content = File::read($this->file);
        foreach ($content as $line) {
            if (preg_match("/^;" . self::TEXT_CREATE_USER_ID . ":" . $this->userId . "/", $line)) {
                return true;
            }
        }
        return false;
    }

    private function allowVote($content, $vote, &$err)
    {
        $voteType = 0;
        $itemTotal = 0;
        foreach ($content as $line) {
            if (preg_match("/^;" . self::TEXT_VOTE_TYPE . ":(.*)$/", $line, $match)) {
                $voteType = intval($match[1]);
            } elseif (preg_match("/^;" . self::TEXT_VOTE_ITEMS . ":(.*)$/", $line, $match)) {
                $itemTotal = count(explode("|", $match[1]));
            } elseif (preg_match("/^" . $this->userId . "\|/", $line)) {
                $err = self::MSG_VOTE_DUPLICATE_ERR;
                return false;
            }
        }
        foreach ($vote as $num) {
            if ($num > ($itemTotal)) {
                $err = sprintf(self::MSG_VOTE_NUM_NOT_EXIST . $num);
                return false;
            }
        }
        if ($voteType < count($vote)) {
            $err = sprintf(self::MSG_VOTE_NUM_ERR, $voteType);
            return false;
        }
        return true;
    }

    function getData()
    {
        if (!$this->checkPrivilege()) {
            return self::MSG_NOT_ALLOW_ERR;
        }
        switch ($this->curCmd) {
            case self::CMD_RESET:
            case self::CMD_CREATE:
                $mode = $this->curCmd == self::CMD_RESET ? File::MODE_TRUNCATE : File::MODE_CREATE_OPEN;
                $success = $this->curCmd == self::CMD_RESET ? self::MSG_RESET_OK : self::MSG_CREATE_OK;
                $fail = $this->curCmd == self::CMD_RESET ? self::MSG_RESET_ERR : self::MSG_CREATE_ERR;
                //create file
                $head = sprintf($this->headTemplate,
                    $this->userId,
                    $this->userName,
                    date("Y-m-d H:i:s"),
                    $this->curPar[self::TEXT_VOTE_NAME],
                    $this->curPar[self::TEXT_VOTE_TYPE],
                    implode("|", $this->curPar[self::TEXT_VOTE_ITEMS])
                );
                if (File::create(
                    $head,
                    $this->file,
                    $mode
                )
                ) {
                    $content = File::read($this->file);
                    return $success . "\r\n" . $this->renderOverview($content);
                } else {
                    BotLogger::error(self::MSG_CREATE_ERR, __FILE__, __LINE__);
                    return $fail . "-" . File::$ERROR;
                }
            case self::CMD_DELETE:
                if (File::delete($this->file)) {
                    return self::MSG_DELETE_OK;
                } else {
                    BotLogger::error(self::MSG_DELETE_ERR, __FILE__, __LINE__, $this->file);
                    return self::MSG_DELETE_ERR . "-" . File::$ERROR;
                }
            case self::CMD_CHECK:
                $content = File::read($this->file);
                if ($content && count($content) > 1) {
                    return $this->renderOverview($content);
                } else {
                    return self::MSG_CHECK_ERR . "-" . File::$ERROR;
                }
            case self::CMD_VOTE:
                $content = File::read($this->file);
                if (!$content || count($content) < 1) {
                    return self::MSG_READ_ERR . "-" . File::$ERROR;
                }
                $err = '';
                if (!$this->allowVote($content, $this->curPar[self::TEXT_VOTE_ITEMS], $err)) {
                    return $err;
                }
                $line = sprintf($this->lineTemplate,
                    $this->userId,
                    $this->userName,
                    implode(",", $this->curPar[self::TEXT_VOTE_ITEMS]),
                    time()
                );
                if (File::append(
                    $line,
                    $this->file
                )
                ) {
                    $content = File::read($this->file);
                    return self::MSG_VOTE_OK . "\r\n" . $this->renderOverview($content);
                } else {
                    BotLogger::error(self::MSG_VOTE_ERR, __FILE__, __LINE__);
                    return self::MSG_VOTE_ERR . "-" . File::$ERROR;
                }


            default:
        }
        return self::MSG_DEFAULT;
    }

    function decodePar($par, $cmd)
    {
        if (!preg_match("/^\[(.*)\]$/", $par, $match)) {
            return false;
        }
        $ret = array();
        $items = explode("|", $match[1]);
        switch ($cmd) {
            case self::CMD_RESET:  //!重置投票[团建活动|1|吃饭|看电影|唱歌]
            case self::CMD_CREATE: //!创建投票[团建活动|1|吃饭|看电影|唱歌]
                if (count($items) < 4) {
                    return false;   //at least 2 item
                }
                $ret[self::TEXT_VOTE_NAME] = $items[0] != "" ? $items[0] : self::TEXT_VOTE_NAME_DEFAULT;
                $ret[self::TEXT_VOTE_TYPE] = $items[1] != "" ? intval($items[1]) : 1;
                for ($i = 2; $i < count($items); $i++) {
                    $ret[self::TEXT_VOTE_ITEMS][] = $items[$i];
                }
                break;
            case self::CMD_DELETE: //!删除投票[团建活动]
            case self::CMD_CHECK:  //!查看投票[团建活动]
                if (count($items) < 1) {
                    return false;
                }
                $ret[self::TEXT_VOTE_NAME] = $items[0] != "" ? $items[0] : self::TEXT_VOTE_NAME_DEFAULT;
                break;
            case self::CMD_VOTE: //!我要投票[团建活动|1|2|] ;最少选一个
                if (count($items) < 2) {
                    return false;
                }
                $ret[self::TEXT_VOTE_NAME] = $items[0] != "" ? $items[0] : self::TEXT_VOTE_NAME_DEFAULT;
                for ($i = 1; $i < count($items); $i++) {
                    $ret[self::TEXT_VOTE_ITEMS][] = intval($items[$i]); //only allow selected item num
                }
                break;
            default:
                return false;
        }

        return $ret;
    }

    function setup(...$para)
    {
        $this->curCmd = $para[0][1];

        if ($this->curCmd == "" || !array_key_exists($this->curCmd, $this->cmd)) {
            BotLogger::error(ERR_PARAMS, __FILE__, __LINE__, $this->curCmd);
            return false;
        }

        $this->curPar = $this->decodePar($para[0][2], $this->curCmd);
        if ($this->curPar == "" || !is_array($this->curPar)) {
            BotLogger::error(ERR_PARAMS, __FILE__, __LINE__, $this->curPar);
            return false;
        }

        $this->userId = $para[1][SynoBot::$PAY_LOAD_USER_ID];
        $this->userName = $para[1][SynoBot::$PAY_LOAD_USERNAME];
        $this->channelId = $para[1][SynoBot::$PAY_LOAD_CHANNEL_ID];

        $this->file = TEMP_PATH . DIRECTORY_SEPARATOR . $this->channelId .
            DIRECTORY_SEPARATOR . md5($this->curPar[self::TEXT_VOTE_NAME]);
        return true;
    }

}
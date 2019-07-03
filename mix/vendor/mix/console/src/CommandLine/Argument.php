<?php

namespace Mix\Console\CommandLine;

/**
 * Class Argument
 * @package Mix\Console
 * @author liu,jian <coder.keda@gmail.com>
 */
class Argument
{

    /**
     * 获取脚本
     * @return string
     */
    public static function script()
    {
        $argv = $GLOBALS['argv'];
        return $argv[0];
    }

    /**
     * 获取命令
     * @return string
     */
    public static function command()
    {
        static $command;
        if (!isset($command)) {
            $argv    = $GLOBALS['argv'];
            $command = $argv[1] ?? '';
            $command = preg_match('/^[a-zA-Z0-9_\-:]+$/i', $command) ? $command : '';
            $command = substr($command, 0, 1) == '-' ? '' : $command;
        }
        return $command;
    }

    /**
     * 获取子命令
     * @return string
     */
    public static function subCommand()
    {
        if (self::command() == '') {
            return '';
        }
        static $subCommand;
        if (!isset($subCommand)) {
            $argv       = $GLOBALS['argv'];
            $subCommand = $argv[2] ?? '';
            $subCommand = preg_match('/^[a-zA-Z0-9_\-:]+$/i', $subCommand) ? $subCommand : '';
            $subCommand = substr($subCommand, 0, 1) == '-' ? '' : $subCommand;
        }
        return $subCommand;
    }

}

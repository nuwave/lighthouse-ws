<?php

namespace Nuwave\Lighthouse\Subscriptions\Support;

use Ratchet\ConnectionInterface;
use Exception;

class Log
{
    protected static $ANSI_CODES = array(
        "off"        => 0,
        "bold"       => 1,
        "italic"     => 3,
        "underline"  => 4,
        "blink"      => 5,
        "inverse"    => 7,
        "hidden"     => 8,
        "black"      => 30,
        "red"        => 31,
        "green"      => 32,
        "yellow"     => 33,
        "blue"       => 34,
        "magenta"    => 35,
        "cyan"       => 36,
        "white"      => 37,
        "black_bg"   => 40,
        "red_bg"     => 41,
        "green_bg"   => 42,
        "yellow_bg"  => 43,
        "blue_bg"    => 44,
        "magenta_bg" => 45,
        "cyan_bg"    => 46,
        "white_bg"   => 47
    );

    public static function v($type, $conn, $message)
    {
        $conn = ($conn instanceof ConnectionInterface) ? $conn->resourceId : '';
        $color = ($type == 'R') ? 'yellow' : (($type == 'S') ? 'green' : 'magenta');
        $spliter = self::set('|', 'white');

        echo self::set(date('y/m/d H:i:s'), $color). $spliter;
        echo self::set($type, $color). $spliter;
        echo self::set(str_pad($conn, 3, ' ', STR_PAD_LEFT), $color). $spliter;
        echo self::set($message, $color). PHP_EOL;
    }

    public static function e(Exception $e)
    {
        $color = 'red_bg+white';
        echo self::set($e->getMessage(), $color). PHP_EOL;
        echo self::set($e->getTraceAsString(), $color). PHP_EOL;
    }

    public static function set($str, $color)
    {
        $color_attrs = explode("+", $color);
        $ansi_str = "";
        foreach ($color_attrs as $attr) {
            $ansi_str .= "\033[" . self::$ANSI_CODES[$attr] . "m";
        }
        $ansi_str .= $str . "\033[" . self::$ANSI_CODES["off"] . "m";
        return $ansi_str;
    }
}

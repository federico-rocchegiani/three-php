<?php

namespace ThreePHP\Math;


class MathUtils
{
    public static function clamp($value, $min, $max)
    {

        return max($min, min($max, $value));
    }

    public static function rand()
    {
        return mt_rand() / mt_getrandmax();
    }

    public static function uuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function denormalize($value, $array)
    {
        return $value;
    }

    public static function normalize($value, $array)
    {
        return $value;
    }

    public static function sign($value)
    {
        return $value <=> 0;
    }

    public static function radiansToDegrees($radians)
    {
        return $radians * 180 / M_PI;
    }

    public static function degreesToRadians($degrees)
    {
        return  M_PI * $degrees / 180;
    }
}

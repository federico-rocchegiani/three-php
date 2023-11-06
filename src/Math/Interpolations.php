<?php

namespace ThreePHP\Math;


class Interpolations
{
    public static function CatmullRom($t, $p0, $p1, $p2, $p3)
    {
        $v0 = ($p2 - $p0) * 0.5;
        $v1 = ($p3 - $p1) * 0.5;
        $t2 = $t * $t;
        $t3 = $t * $t2;
        return (2 * $p1 - 2 * $p2 + $v0 + $v1) * $t3 + (-3 * $p1 + 3 * $p2 - 2 * $v0 - $v1) * $t2 + $v0 * $t + $p1;
    }

    private static function QuadraticBezierP0($t, $p)
    {
        $k = 1 - $t;
        return $k * $k * $p;
    }

    private static function QuadraticBezierP1($t, $p)
    {
        return 2 * (1 - $t) * $t * $p;
    }

    private static function QuadraticBezierP2($t, $p)
    {
        return $t * $t * $p;
    }

    public static function QuadraticBezier($t, $p0, $p1, $p2)
    {
        return static::QuadraticBezierP0($t, $p0) + static::QuadraticBezierP1($t, $p1) + static::QuadraticBezierP2($t, $p2);
    }

    private static function CubicBezierP0($t, $p)
    {
        $k = 1 - $t;
        return $k * $k * $k * $p;
    }

    private static function CubicBezierP1($t, $p)
    {
        $k = 1 - $t;
        return 3 * $k * $k * $t * $p;
    }

    private static function CubicBezierP2($t, $p)
    {
        return 3 * (1 - $t) * $t * $t * $p;
    }

    private static function CubicBezierP3($t, $p)
    {
        return $t * $t * $t * $p;
    }

    public static function CubicBezier($t, $p0, $p1, $p2, $p3)
    {
        return static::CubicBezierP0($t, $p0) + static::CubicBezierP1($t, $p1) + static::CubicBezierP2($t, $p2) + static::CubicBezierP3($t, $p3);
    }
}

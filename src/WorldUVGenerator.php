<?php

namespace ThreePHP;

use ThreePHP\Math\Vector2;

class WorldUVGenerator
{
    public static function generateTopUV($geometry, $vertices, $indexA, $indexB, $indexC)
    {
        $a_x = $vertices[$indexA * 3];
        $a_y = $vertices[$indexA * 3 + 1];
        $b_x = $vertices[$indexB * 3];
        $b_y = $vertices[$indexB * 3 + 1];
        $c_x = $vertices[$indexC * 3];
        $c_y = $vertices[$indexC * 3 + 1];
        return [
            new Vector2($a_x, $a_y),
            new Vector2($b_x, $b_y),
            new Vector2($c_x, $c_y)
        ];
    }

    public static function generateSideWallUV($geometry, $vertices, $indexA, $indexB, $indexC, $indexD)
    {
        $a_x = $vertices[$indexA * 3];
        $a_y = $vertices[$indexA * 3 + 1];
        $a_z = $vertices[$indexA * 3 + 2];
        $b_x = $vertices[$indexB * 3];
        $b_y = $vertices[$indexB * 3 + 1];
        $b_z = $vertices[$indexB * 3 + 2];
        $c_x = $vertices[$indexC * 3];
        $c_y = $vertices[$indexC * 3 + 1];
        $c_z = $vertices[$indexC * 3 + 2];
        $d_x = $vertices[$indexD * 3];
        $d_y = $vertices[$indexD * 3 + 1];
        $d_z = $vertices[$indexD * 3 + 2];
        if (abs($a_y - $b_y) < abs($a_x - $b_x)) {
            return [
                new Vector2($a_x, 1 - $a_z),
                new Vector2($b_x, 1 - $b_z),
                new Vector2($c_x, 1 - $c_z),
                new Vector2($d_x, 1 - $d_z)
            ];
        } else {
            return [
                new Vector2($a_y, 1 - $a_z),
                new Vector2($b_y, 1 - $b_z),
                new Vector2($c_y, 1 - $c_z),
                new Vector2($d_y, 1 - $d_z)
            ];
        }
    }
}

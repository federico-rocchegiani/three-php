<?php

namespace ThreePHP\Paths;


class ShapeUtils
{
    // Calcola l'area del poligono di contorno
    public static function area($contour)
    {
        $n = count($contour);
        $a = 0.0;
        for ($p = $n - 1, $q = 0; $q < $n; $p = $q++) {
            $a += $contour[$p]->x * $contour[$q]->y - $contour[$q]->x * $contour[$p]->y;
        }
        return $a * 0.5;
    }

    public static function isClockWise($pts)
    {
        return self::area($pts) < 0;
    }

    public static function triangulateShape(&$contour, &$holes)
    {
        $vertices = []; // array piatto di vertici come [ x0, y0, x1, y1, x2, y2, ... ]
        $holeIndices = []; // array di indici di buchi
        $faces = []; // array finale di indici di vertici come [ [a, b, d], [b, c, d] ]
        self::removeDupEndPts($contour);
        self::addContour($vertices, $contour);
        //
        $holeIndex = count($contour);
        foreach ($holes as &$hole) {
            self::removeDupEndPts($hole);
            $holeIndices[] = $holeIndex;
            $holeIndex += count($hole);
            self::addContour($vertices, $hole);
        }
        //
        $triangles = Earcut::triangulate($vertices, $holeIndices);
        //
        for ($i = 0; $i < count($triangles); $i += 3) {
            $faces[] = array_slice($triangles, $i, 3);
        }
        return $faces;
    }

    public static function removeDupEndPts(&$points)
    {
        $l = count($points);
        if ($l > 2 && $points[$l - 1]->equals($points[0])) {
            array_pop($points);
        }
    }

    public static function addContour(&$vertices, $contour)
    {
        foreach ($contour as $point) {
            $vertices[] = $point->x;
            $vertices[] = $point->y;
        }
    }
}

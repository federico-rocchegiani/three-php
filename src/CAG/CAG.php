<?php

namespace ThreePHP\CAG;

use Exception;
use ThreePHP\CSG\CSG;
use ThreePHP\CSG\Vector2D;
use ThreePHP\CSG\Vertex;

class CAG
{
    private $sides = [];

    public static function fromSides($sides)
    {
        $cag = new CAG();
        $cag->sides = $sides;
        return $cag;
    }

    public function isSelfIntersecting($debug = false)
    {
        $numsides = count($this->sides);
        for ($i = 0; $i < $numsides; $i++) {
            $side0 = $this->sides[$i];
            for ($ii = $i + 1; $ii < $numsides; $ii++) {
                $side1 = $this->sides[$ii];
                if (CAG::linesIntersect($side0->vertex0->pos, $side0->vertex1->pos, $side1->vertex0->pos, $side1->vertex1->pos)) {
                    if ($debug) {
                    }
                    return true;
                }
            }
        }
        return false;
    }

    // public static function fromPoints($points)
    // {
    //     $numpoints = count($points);
    //     if ($numpoints < 3) {
    //         throw new Exception("CAG shape needs at least 3 points");
    //     }
    //     $sides = [];
    //     $prevpoint = new Vector2D($points[$numpoints - 1]);
    //     $prevvertex = new Vertex($prevpoint);
    //     foreach ($points as $p) {
    //         $point = new Vector2D($p);
    //         $vertex = new Vertex($point);
    //         $side = new Side($prevvertex, $vertex);
    //         $sides[] = $side;
    //         $prevvertex = $vertex;
    //     }
    //     $result = CAG::fromSides($sides);
    //     if ($result->isSelfIntersecting()) {
    //         throw new Exception("Polygon is self intersecting!");
    //     }
    //     $area = $result->area();
    //     if (abs($area) < 1e-5) {
    //         throw new Exception("Degenerate polygon!");
    //     }
    //     if ($area < 0) {
    //         $result = $result->flipped();
    //     }
    //     $result = $result->canonicalized();
    //     return $result;
    // }

    public static function fromPointsNoCheck($points)
    {
        $sides = [];
        $prevpoint = new Vector2D($points[count($points) - 1]);
        $prevvertex = new Vertex($prevpoint);
        foreach ($points as $p) {
            $point = new Vector2D($p);
            $vertex = new Vertex($point);
            $side = new Side($prevvertex, $vertex);
            $sides[] = $side;
            $prevvertex = $vertex;
        }
        return CAG::fromSides($sides);
    }

    public static function fromFakeCSG($csg)
    {
        $sides = array_filter(
            array_map(
                function ($p) {
                    return Side::fromFakePolygon($p);
                },
                $csg->polygons
            ),
            function ($s) {
                return $s !== null;
            }
        );
        return CAG::fromSides($sides);
    }

    public static function linesIntersect($p0start, $p0end, $p1start, $p1end)
    {
        if ($p0end->equals($p1start) || $p1end->equals($p0start)) {
            $d = $p1end->minus($p1start)->unit()->plus($p0end->minus($p0start)->unit())->length();
            if ($d < 1e-5) {
                return true;
            }
        } else {
            $d0 = $p0end->minus($p0start);
            $d1 = $p1end->minus($p1start);
            if (abs($d0->cross($d1)) < 1e-9) {
                return false;
            }
            $alphas = CSG::solve2Linear(-$d0->x, $d1->x, -$d0->y, $d1->y, $p0start->x - $p1start->x, $p0start->y - $p1start->y);
            if (($alphas[0] > 1e-6) && ($alphas[0] < 0.999999) && ($alphas[1] > 1e-5) && ($alphas[1] < 0.999999)) {
                return true;
            }
        }
        return false;
    }
}

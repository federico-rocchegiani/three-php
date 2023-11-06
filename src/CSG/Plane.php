<?php

namespace ThreePHP\CSG;

class Plane
{
    const EPSILON = 1e-5;

    const COPLANAR = 0;
    const FRONT = 1;
    const BACK = 2;
    const SPANNING = 3;

    public $normal;
    public $w;

    public function __construct($normal, $w)
    {
        $this->normal = $normal;
        $this->w = $w;
    }

    public function clone()
    {
        return new Plane($this->normal->clone(), $this->w);
    }

    public function flip()
    {
        $this->normal->negate();
        $this->w = -$this->w;
        return $this;
    }

    // Split `polygon` by this plane if needed, then put the polygon or polygon
    // fragments in the appropriate lists. Coplanar polygons go into either
    // `coplanarFront` or `coplanarBack` depending on their orientation with
    // respect to this plane. Polygons in front or in back of this plane go into
    // either `front` or `back`.
    public function splitPolygon($polygon, &$coplanarFront, &$coplanarBack, &$front, &$back)
    {
        // Classify each point as well as the entire polygon into one of the above
        // four classes.
        $polygonType = 0;
        $types = [];
        $vertices_count = count($polygon->vertices);

        for ($i = 0; $i < $vertices_count; $i++) {
            $t = $this->normal->dot($polygon->vertices[$i]->pos) - $this->w;
            $type = ($t < -static::EPSILON) ? static::BACK : (($t > static::EPSILON) ? static::FRONT : static::COPLANAR);
            $polygonType |= $type;
            $types[] = $type;
        }
        // Put the polygon in the correct list, splitting it when necessary.
        switch ($polygonType) {
            case static::COPLANAR:
                if ($this->normal->dot($polygon->plane->normal) > 0) {
                    $coplanarFront[] = $polygon;
                } else {
                    $coplanarBack[] = $polygon;
                }
                break;
            case static::FRONT:
                $front[] = $polygon;
                break;
            case static::BACK:
                $back[] = $polygon;
                break;
            case static::SPANNING:
                $f = [];
                $b = [];

                for ($i = 0; $i < $vertices_count; $i++) {
                    $j = ($i + 1) % $vertices_count;
                    $ti = $types[$i];
                    $tj = $types[$j];
                    $vi = $polygon->vertices[$i];
                    $vj = $polygon->vertices[$j];

                    if ($ti != static::BACK) {
                        $f[] = $vi;
                    }

                    if ($ti != static::FRONT) {
                        $b[] = $ti != static::BACK ? $vi->clone() : $vi;
                    }

                    if (($ti | $tj) == static::SPANNING) {
                        $t = ($this->w - $this->normal->dot($vi->pos)) / $this->normal->dot($vj->pos->clone()->sub($vi->pos));
                        $v = $vi->interpolate($vj, $t);
                        $f[] = $v;
                        $b[] = $v->clone();
                    }
                }

                if (count($f)  >= 3) {
                    $front[] = new Polygon($f, $polygon->shared);
                }

                if (count($b) >= 3) {
                    $back[] = new Polygon($b, $polygon->shared);
                }
                break;
        }
    }

    public static function fromPoints($a, $b, $c)
    {
        $n = $b->clone()->sub($a)->cross($c->clone()->sub($a))->normalize();
        return new Plane($n->clone(), $n->dot($a));
    }
}

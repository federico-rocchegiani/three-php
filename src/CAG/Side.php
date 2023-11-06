<?php

namespace ThreePHP\CAG;

use Exception;
use ThreePHP\CSG\CSG;
use ThreePHP\CSG\Polygon;
use ThreePHP\CSG\Vector2D;
use ThreePHP\CSG\Vertex as CSG_Vertex;

class Side
{
    public $vertex0;
    public $vertex1;
    private $tag;

    public function __construct($vertex0, $vertex1)
    {
        if (!($vertex0 instanceof Vertex)) {
            throw new Exception("Assertion failed");
        }
        if (!($vertex1 instanceof Vertex)) {
            throw new Exception("Assertion failed");
        }
        $this->vertex0 = $vertex0;
        $this->vertex1 = $vertex1;
    }

    public static function fromFakePolygon($polygon)
    {
        foreach ($polygon->vertices as $v) {
            if (!(abs($v->pos->z) >= 0.999 && abs($v->pos->z) < 1.001)) {
                throw new Exception("Assertion failed: _fromFakePolygon expects abs z values of 1");
            }
        }

        if (count($polygon->vertices) < 4) {
            return null;
        }

        $reverse = false;
        $vert1Indices = [];
        $pts2d = array_filter($polygon->vertices, function ($v, $i) use (&$vert1Indices) {
            if ($v->pos->z > 0) {
                $vert1Indices[] = $i;
                return true;
            }
        });

        $pts2d = array_map(function ($v) {
            return new Vector2D($v->pos->x, $v->pos->y);
        }, $pts2d);

        if (count($pts2d) !== 2) {
            throw new Exception("Assertion failed: _fromFakePolygon: not enough points found");
        }

        $d = $vert1Indices[1] - $vert1Indices[0];

        if ($d === 1 || $d === 3) {
            if ($d === 1) {
                $reverse = true;
            }
        } else {
            throw new Exception("Assertion failed: _fromFakePolygon: unknown index ordering");
        }

        $vertex0 = new Vertex($pts2d[0]);
        $vertex1 = new Vertex($pts2d[1]);

        if ($reverse) {
            return new Side($vertex1, $vertex0);
        }

        return new Side($vertex0, $vertex1);
    }

    public function toString()
    {
        return $this->vertex0->toString() . " -> " . $this->vertex1->toString();
    }

    public function toPolygon3D($z0, $z1)
    {
        $vertices = [
            new CSG_Vertex($this->vertex0->pos->toVector3D($z0)),
            new CSG_Vertex($this->vertex1->pos->toVector3D($z0)),
            new CSG_Vertex($this->vertex1->pos->toVector3D($z1)),
            new CSG_Vertex($this->vertex0->pos->toVector3D($z1))
        ];

        return new Polygon($vertices);
    }

    public function transform($matrix4x4)
    {
        $newp1 = $this->vertex0->pos->transform($matrix4x4);
        $newp2 = $this->vertex1->pos->transform($matrix4x4);
        return new Side(new Vertex($newp1), new Vertex($newp2));
    }

    public function flipped()
    {
        return new Side($this->vertex1, $this->vertex0);
    }

    public function direction()
    {
        return $this->vertex1->pos->minus($this->vertex0->pos);
    }

    public function getTag()
    {
        $result = $this->tag;
        if (!$result) {
            $result = CSG::getTag();
            $this->tag = $result;
        }
        return $result;
    }

    public function lengthSquared()
    {
        $x = $this->vertex1->pos->x - $this->vertex0->pos->x;
        $y = $this->vertex1->pos->y - $this->vertex0->pos->y;
        return $x * $x + $y * $y;
    }

    public function length()
    {
        return sqrt($this->lengthSquared());
    }
}

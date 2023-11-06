<?php

namespace ThreePHP\CSG;

class Node
{

    public $polygons;
    public ?Node $front = null;
    public ?Node $back = null;
    public Plane $plane;

    public function __construct(array $polygons = [])
    {
        $this->polygons = [];
        if (!empty($polygons)) {
            $this->build($polygons);
        }
    }

    public function clone()
    {
        $node = new Node();
        $node->plane = $this->plane?->clone();
        $node->front = $this->front?->clone();
        $node->back = $this->back?->clone();
        $node->polygons = array_map(fn ($p) => $p->clone(), $this->polygons);
        return $node;
    }

    // Convert solid space to empty space and empty space to solid space.
    public function invert()
    {
        for ($i = 0; $i < count($this->polygons); $i++) {
            $this->polygons[$i]->flip();
        }

        if (!empty($this->plane)) {
            $this->plane->flip();
        }
        if (!empty($this->front)) {
            $this->front->invert();
        }
        if (!empty($this->back)) {
            $this->back->invert();
        }
        $tmp = $this->front;
        $this->front = $this->back;
        $this->back = $tmp;
    }

    // Recursively remove all polygons in `polygons` that are inside $this-> BSP
    // tree.
    public function clipPolygons($polygons)
    {
        if (empty($this->plane)) {
            return $polygons;
        }
        $front = [];
        $back = [];
        for ($i = 0; $i < count($polygons); $i++) {
            $this->plane->splitPolygon($polygons[$i], $front, $back, $front, $back);
        }
        if (!empty($this->front)) {
            $front = $this->front->clipPolygons($front);
        }
        if (!empty($this->back)) {
            $back = $this->back->clipPolygons($back);
        } else {
            $back = [];
        }
        //return front;
        return [...$front, ...$back];
    }

    // Remove all polygons in $this-> BSP tree that are inside the other BSP tree
    // `bsp`.
    public function clipTo($bsp)
    {
        $this->polygons = $bsp->clipPolygons($this->polygons);
        if (!empty($this->front)) {
            $this->front->clipTo($bsp);
        }
        if (!empty($this->back)) {
            $this->back->clipTo($bsp);
        }
    }

    // Return a list of all polygons in $this-> BSP tree.
    public function allPolygons()
    {
        $polygons = $this->polygons;
        if (!empty($this->front)) {
            $polygons = [...$polygons, ...$this->front->allPolygons()];
        }
        if (!empty($this->back)) {
            $polygons = [...$polygons, ...$this->back->allPolygons()];
        }
        return $polygons;
    }

    // Build a BSP tree out of `polygons`. When called on an existing tree, the
    // new polygons are filtered down to the bottom of the tree and become new
    // nodes there. Each set of polygons is partitioned using the first polygon
    // (no heuristic is used to pick a good split).
    public function build(array $polygons)
    {
        if (empty($polygons)) {
            return;
        }

        if (empty($this->plane)) {
            $this->plane = $polygons[0]->plane->clone();
        }

        $front = [];
        $back = [];

        for ($i = 0; $i < count($polygons); $i++) {
            $this->plane->splitPolygon($polygons[$i], $this->polygons, $this->polygons, $front, $back);
        }

        if (!empty($front)) {
            if (empty($this->front)) {
                $this->front = new Node();
            }
            $this->front->build($front);
        }

        if (!empty($back)) {
            if (empty($this->back)) {
                $this->back = new Node();
            }
            $this->back->build($back);
        }
    }

    public static function fromJson($json)
    {
        $polygons = array_map(function ($p) {
            $vertices = array_map(function ($v) {
                return new Vertex($v['pos'], $v['normal'], $v['uv']);
            }, $p['vertices']);
            return new Polygon($vertices, $p['shared']);
        }, $json['polygons']);

        return CSG::fromPolygons($polygons);
    }
}

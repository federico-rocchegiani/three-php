<?php

namespace ThreePHP\CSG;

use ThreePHP\CSG\Plane;

class Polygon
{
    public $vertices;
    public $shared;
    public $plane;

    public function __construct($vertices, $shared)
    {
        $this->vertices = $vertices;
        $this->shared = $shared;
        $this->plane = Plane::fromPoints($vertices[0]->pos, $vertices[1]->pos, $vertices[2]->pos);
    }

    public function clone()
    {
        return new Polygon(array_map(fn ($v) => $v->clone(), $this->vertices), $this->shared);
    }

    public function flip()
    {
        $this->vertices = array_reverse($this->vertices);
        array_walk($this->vertices, fn (&$v) => $v->flip());
        $this->plane->flip();
    }
}

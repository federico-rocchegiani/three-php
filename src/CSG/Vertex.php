<?php

namespace ThreePHP\CSG;

use ThreePHP\Math\Vector2;
use ThreePHP\Math\Vector3;

class Vertex
{
    public Vector3 $pos;
    public Vector3 $normal;
    public ?Vector2 $uv;
    public ?Vector3 $color;


    public function __construct(Vector3 $pos, Vector3 $normal, ?Vector2 $uv = null, ?Vector3 $color = null)
    {
        $this->pos = $pos->clone();
        $this->normal = $normal->clone();
        $this->color = $color?->clone();
        if (!empty($uv)) {
            $this->uv = $uv->clone();
        }
    }

    public function clone()
    {
        return new Vertex($this->pos, $this->normal, $this->uv, $this->color);
    }

    // Invert all orientation-specific data (e.g. vertex normal). Called when the
    // orientation of a polygon is flipped.
    public function flip()
    {
        $this->normal->negate();
        return $this;
    }

    // Create a new vertex between this vertex and `other` by linearly
    // interpolating all properties using a parameter of `t`. Subclasses should
    // override this to interpolate additional properties.
    public function interpolate($other, $t)
    {
        $uv = null;
        if (!empty($this->uv) && !empty($other->uv)) {
            $uv = $this->uv->clone()->lerp($other->uv, $t);
        }

        $color = null;
        if (!empty($this->color) && !empty($other->color)) {
            $color = $this->color->clone()->lerp($other->color, $t);
        }

        return new Vertex(
            $this->pos->clone()->lerp($other->pos, $t),
            $this->normal->clone()->lerp($other->normal, $t),
            $uv,
            $color
        );
    }
}

<?php

namespace ThreePHP\Paths\Curves;

use ThreePHP\Math\Interpolations;
use ThreePHP\Math\Vector2;
use ThreePHP\Paths\Curve;

class CubicBezierCurve extends Curve
{
    public Vector2 $v0;
    public Vector2 $v1;
    public Vector2 $v2;
    public Vector2 $v3;

    public $isCubicBezierCurve;

    public function __construct(Vector2 $v0 = null, Vector2 $v1 = null, Vector2 $v2 = null, Vector2 $v3 = null)
    {
        parent::__construct();
        $this->isCubicBezierCurve = true;
        $this->type = 'CubicBezierCurve';
        $this->v0 = $v0 ?: new Vector2();
        $this->v1 = $v1 ?: new Vector2();
        $this->v2 = $v2 ?: new Vector2();
        $this->v3 = $v3 ?: new Vector2();
    }

    public function getPoint($t, $optionalTarget = null): mixed
    {
        $point = $optionalTarget ?: new Vector2();
        $v0 = $this->v0;
        $v1 = $this->v1;
        $v2 = $this->v2;
        $v3 = $this->v3;
        $point->set(
            Interpolations::CubicBezier($t, $v0->x, $v1->x, $v2->x, $v3->x),
            Interpolations::CubicBezier($t, $v0->y, $v1->y, $v2->y, $v3->y)
        );
        return $point;
    }

    public function copy($source): self
    {
        parent::copy($source);
        $this->v0->copy($source->v0);
        $this->v1->copy($source->v1);
        $this->v2->copy($source->v2);
        $this->v3->copy($source->v3);
        return $this;
    }

    public function toJSON(): array
    {
        $data = parent::toJSON();
        $data['v0'] = $this->v0->toArray();
        $data['v1'] = $this->v1->toArray();
        $data['v2'] = $this->v2->toArray();
        $data['v3'] = $this->v3->toArray();
        return $data;
    }

    public function fromJSON($json)
    {
        parent::fromJSON($json);
        $this->v0->fromArray($json['v0']);
        $this->v1->fromArray($json['v1']);
        $this->v2->fromArray($json['v2']);
        $this->v3->fromArray($json['v3']);
        return $this;
    }
}

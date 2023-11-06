<?php

namespace ThreePHP\Paths\Curves;

use ThreePHP\Math\Interpolations;
use ThreePHP\Math\Vector2;
use ThreePHP\Paths\Curve;

class QuadraticBezierCurve extends Curve
{
    public $v0, $v1, $v2;
    protected $isQuadraticBezierCurve;

    public function __construct($v0 = null, $v1 = null, $v2 = null)
    {
        parent::__construct();
        $this->isQuadraticBezierCurve = true;
        $this->type = 'QuadraticBezierCurve';
        $this->v0 = $v0 ?: new Vector2();
        $this->v1 = $v1 ?: new Vector2();
        $this->v2 = $v2 ?: new Vector2();
    }

    public function getPoint($t, $optionalTarget = null)
    {
        $point = $optionalTarget ?: new Vector2();
        $v0 = $this->v0;
        $v1 = $this->v1;
        $v2 = $this->v2;

        $point->set(
            Interpolations::QuadraticBezier($t, $v0->x, $v1->x, $v2->x),
            Interpolations::QuadraticBezier($t, $v0->y, $v1->y, $v2->y)
        );

        return $point;
    }

    public function copy($source)
    {
        parent::copy($source);
        $this->v0->copy($source->v0);
        $this->v1->copy($source->v1);
        $this->v2->copy($source->v2);
        return $this;
    }

    public function toJSON()
    {
        $data = parent::toJSON();
        $data['v0'] = $this->v0->toArray();
        $data['v1'] = $this->v1->toArray();
        $data['v2'] = $this->v2->toArray();
        return $data;
    }

    public function fromJSON($json)
    {
        parent::fromJSON($json);
        $this->v0->fromArray($json['v0']);
        $this->v1->fromArray($json['v1']);
        $this->v2->fromArray($json['v2']);
        return $this;
    }
}

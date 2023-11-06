<?php

namespace ThreePHP\Paths\Curves;

use ThreePHP\Math\Interpolations;
use ThreePHP\Math\Vector2;
use ThreePHP\Paths\Curve;

class SplineCurve extends Curve
{
    public $points;
    public $isSplineCurve;

    public function __construct($points = [])
    {
        parent::__construct();
        $this->isSplineCurve = true;
        $this->type = 'SplineCurve';
        $this->points = $points;
    }

    public function getPoint($t, $optionalTarget = null)
    {
        $point = $optionalTarget ?? new Vector2();
        $points = $this->points;
        $p = (count($points) - 1) * $t;
        $intPoint = floor($p);
        $weight = $p - $intPoint;
        $p0 = $points[$intPoint === 0 ? $intPoint : $intPoint - 1];
        $p1 = $points[$intPoint];
        $p2 = $points[$intPoint > count($points) - 2 ? count($points) - 1 : $intPoint + 1];
        $p3 = $points[$intPoint > count($points) - 3 ? count($points) - 1 : $intPoint + 2];

        $point->set(
            Interpolations::CatmullRom($weight, $p0->x, $p1->x, $p2->x, $p3->x),
            Interpolations::CatmullRom($weight, $p0->y, $p1->y, $p2->y, $p3->y)
        );

        return $point;
    }

    public function copy($source)
    {
        parent::copy($source);
        $this->points = [];

        foreach ($source->points as $point) {
            $this->points[] = $point->clone();
        }

        return $this;
    }

    public function toJSON()
    {
        $data = parent::toJSON();
        $data['points'] = [];

        foreach ($this->points as $point) {
            $data['points'][] = $point->toArray();
        }

        return $data;
    }

    public function fromJSON($json)
    {
        parent::fromJSON($json);
        $this->points = [];

        foreach ($json['points'] as $point) {
            $this->points[] = (new Vector2())->fromArray($point);
        }

        return $this;
    }
}

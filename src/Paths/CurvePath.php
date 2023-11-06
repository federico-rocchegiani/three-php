<?php

namespace ThreePHP\Paths;

use ThreePHP\Curves\LineCurve;

class CurvePath extends Curve
{
    public $curves;
    public $autoClose;
    protected $cacheLengths;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'CurvePath';
        $this->curves = [];
        $this->autoClose = false;
    }

    public function add($curve)
    {
        $this->curves[] = $curve;
    }

    public function closePath()
    {
        $startPoint = $this->curves[0]->getPoint(0);
        $endPoint = $this->curves[count($this->curves) - 1]->getPoint(1);

        if ($startPoint != $endPoint) {
            $this->curves[] = new LineCurve($endPoint, $startPoint);
        }
    }

    public function getPoint($t, $optionalTarget = null)
    {
        $d = $t * $this->getLength();
        $curveLengths = $this->getCurveLengths();
        $i = 0;

        while ($i < count($curveLengths)) {
            if ($curveLengths[$i] >= $d) {
                $diff = $curveLengths[$i] - $d;
                $curve = $this->curves[$i];
                $segmentLength = $curve->getLength();
                $u = $segmentLength === 0 ? 0 : 1 - $diff / $segmentLength;

                return $curve->getPointAt($u, $optionalTarget);
            }
            $i++;
        }

        return null;
    }

    public function getLength()
    {
        $lengths = $this->getCurveLengths();
        return $lengths[count($lengths) - 1];
    }

    public function updateArcLengths()
    {
        $this->needsUpdate = true;
        $this->cacheLengths = null;
        $this->getCurveLengths();
    }

    public function getCurveLengths()
    {
        if ($this->cacheLengths && count($this->cacheLengths) === count($this->curves)) {
            return $this->cacheLengths;
        }

        $lengths = [];
        $sums = 0;

        for ($i = 0, $l = count($this->curves); $i < $l; $i++) {
            $sums += $this->curves[$i]->getLength();
            $lengths[] = $sums;
        }

        $this->cacheLengths = $lengths;
        return $lengths;
    }

    public function getSpacedPoints($divisions = 40)
    {
        $points = [];

        for ($i = 0; $i <= $divisions; $i++) {
            $points[] = $this->getPoint($i / $divisions);
        }

        if ($this->autoClose) {
            $points[] = $points[0];
        }

        return $points;
    }

    public function getPoints($divisions = 12)
    {
        $points = [];
        $last = null;

        foreach ($this->curves as $curve) {
            $resolution = property_exists($curve, 'isEllipseCurve') ? $divisions * 2 : ((property_exists($curve, 'isLineCurve') || property_exists($curve, 'isLineCurve3')) ? 1 : (property_exists($curve, 'isSplineCurve') ? $divisions * count($curve->points) : $divisions));

            $pts = $curve->getPoints($resolution);

            foreach ($pts as $point) {
                if ($last && $last->equals($point)) {
                    continue;
                }
                $points[] = $point;
                $last = $point;
            }
        }

        if ($this->autoClose && count($points) > 1 && !$points[count($points) - 1]->equals($points[0])) {
            $points[] = $points[0];
        }

        return $points;
    }

    public function copy($source)
    {
        parent::copy($source);
        $this->curves = [];

        foreach ($source->curves as $curve) {
            $this->curves[] = $curve->clone();
        }

        $this->autoClose = $source->autoClose;

        return $this;
    }

    public function toJSON()
    {
        $data = parent::toJSON();
        $data['autoClose'] = $this->autoClose;
        $data['curves'] = [];

        foreach ($this->curves as $curve) {
            $data['curves'][] = $curve->toJSON();
        }

        return $data;
    }

    public function fromJSON($json)
    {
        parent::fromJSON($json);
        $this->autoClose = $json['autoClose'];
        $this->curves = [];

        foreach ($json['curves'] as $curve) {
            $class = $curve['type'];
            $this->curves[] = (new $class())->fromJSON($curve);
        }

        return $this;
    }
}

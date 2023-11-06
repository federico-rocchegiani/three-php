<?php

namespace ThreePHP\Core;

use ThreePHP\Math\Vector3;

class Sphere
{
    public $center;
    public $radius;

    public function __construct($center = null, $radius = -1)
    {
        if ($center === null) {
            $center = new Vector3();
        }

        $this->center = $center;
        $this->radius = $radius;
    }

    public function set($center, $radius)
    {
        $this->center->copy($center);
        $this->radius = $radius;
        return $this;
    }

    public function setFromPoints($points, $optionalCenter = null)
    {
        if ($optionalCenter !== null) {
            $this->center->copy($optionalCenter);
        } else {
            $box = (new Box3())->setFromPoints($points);
            $box->getCenter($this->center);
        }

        $maxRadiusSq = 0;
        for ($i = 0, $il = count($points); $i < $il; $i++) {
            $maxRadiusSq = max($maxRadiusSq, $this->center->distanceToSquared($points[$i]));
        }

        $this->radius = sqrt($maxRadiusSq);
        return $this;
    }

    public function copy($sphere)
    {
        $this->center->copy($sphere->center);
        $this->radius = $sphere->radius;
        return $this;
    }

    public function isEmpty()
    {
        return $this->radius < 0;
    }

    public function makeEmpty()
    {
        $this->center->set(0, 0, 0);
        $this->radius = -1;
        return $this;
    }

    public function containsPoint($point)
    {
        return $point->distanceToSquared($this->center) <= ($this->radius * $this->radius);
    }

    public function distanceToPoint($point)
    {
        return $point->distanceTo($this->center) - $this->radius;
    }

    public function intersectsSphere($sphere)
    {
        $radiusSum = $this->radius + $sphere->radius;
        return $sphere->center->distanceToSquared($this->center) <= ($radiusSum * $radiusSum);
    }

    public function intersectsBox($box)
    {
        return $box->intersectsSphere($this);
    }

    public function intersectsPlane($plane)
    {
        return abs($plane->distanceToPoint($this->center)) <= $this->radius;
    }

    public function clampPoint($point, $target)
    {
        $deltaLengthSq = $this->center->distanceToSquared($point);
        $target->copy($point);

        if ($deltaLengthSq > ($this->radius * $this->radius)) {
            $target->sub($this->center)->normalize();
            $target->multiplyScalar($this->radius)->add($this->center);
        }

        return $target;
    }

    public function getBoundingBox($target)
    {
        if ($this->isEmpty()) {
            $target->makeEmpty();
            return $target;
        }

        $target->set($this->center, $this->center);
        $target->expandByScalar($this->radius);
        return $target;
    }

    public function applyMatrix4($matrix)
    {
        $this->center->applyMatrix4($matrix);
        $this->radius *= $matrix->getMaxScaleOnAxis();
        return $this;
    }

    public function translate($offset)
    {
        $this->center->add($offset);
        return $this;
    }

    public function expandByPoint($point)
    {
        if ($this->isEmpty()) {
            $this->center->copy($point);
            $this->radius = 0;
            return $this;
        }

        $v1 = $point->clone()->sub($this->center);
        $lengthSq = $v1->lengthSq();

        if ($lengthSq > ($this->radius * $this->radius)) {
            $length = sqrt($lengthSq);
            $delta = ($length - $this->radius) * 0.5;
            $this->center->addScaledVector($v1, $delta / $length);
            $this->radius += $delta;
        }

        return $this;
    }

    public function union($sphere)
    {
        if ($sphere->isEmpty()) {
            return $this;
        }

        if ($this->isEmpty()) {
            $this->copy($sphere);
            return $this;
        }

        if ($this->center->equals($sphere->center)) {
            $this->radius = max($this->radius, $sphere->radius);
        } else {
            $v2 = $sphere->center->clone()->sub($this->center)->setLength($sphere->radius);
            $v1 = $sphere->center->clone()->add($v2);
            $this->expandByPoint($v1);
            $v1 = $sphere->center->clone()->sub($v2);
            $this->expandByPoint($v1);
        }

        return $this;
    }

    public function equals($sphere)
    {
        return $sphere->center->equals($this->center) && ($sphere->radius === $this->radius);
    }

    public function clone()
    {
        return (new static())->copy($this);
    }
}

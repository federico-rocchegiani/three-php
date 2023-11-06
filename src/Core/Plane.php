<?php

namespace ThreePHP\Core;

use ThreePHP\Math\Matrix3;
use ThreePHP\Math\Vector3;

class Plane
{
    public $isPlane = true;
    public $normal;
    public $constant;

    public function __construct($normal = new Vector3(1, 0, 0), $constant = 0)
    {
        if ($normal === null) {
            $normal = new Vector3(1, 0, 0);
        }
        $this->normal = $normal;
        $this->constant = $constant;
    }

    public function set($normal, $constant)
    {
        $this->normal->copy($normal);
        $this->constant = $constant;
        return $this;
    }

    public function setComponents($x, $y, $z, $w)
    {
        $this->normal->set($x, $y, $z);
        $this->constant = $w;
        return $this;
    }

    public function setFromNormalAndCoplanarPoint($normal, $point)
    {
        $this->normal->copy($normal);
        $this->constant = -$point->dot($this->normal);
        return $this;
    }

    public function setFromCoplanarPoints($a, $b, $c)
    {
        $normal = $c->subVectors($c, $b)->cross($a->subVectors($a, $b))->normalize();
        $this->setFromNormalAndCoplanarPoint($normal, $a);
        return $this;
    }

    public function copy($plane)
    {
        $this->normal->copy($plane->normal);
        $this->constant = $plane->constant;
        return $this;
    }

    public function normalize()
    {
        $inverseNormalLength = 1.0 / $this->normal->length();
        $this->normal->multiplyScalar($inverseNormalLength);
        $this->constant *= $inverseNormalLength;
        return $this;
    }

    public function negate()
    {
        $this->constant *= -1;
        $this->normal->negate();
        return $this;
    }

    public function distanceToPoint($point)
    {
        return $this->normal->dot($point) + $this->constant;
    }

    public function distanceToSphere($sphere)
    {
        return $this->distanceToPoint($sphere->center) - $sphere->radius;
    }

    public function projectPoint($point, $target)
    {
        return $target->copy($this->normal)->multiplyScalar(-$this->distanceToPoint($point))->add($point);
    }

    public function intersectLine($line, $target)
    {
        $vector = new Vector3();
        $direction = $line->delta($line, $vector);
        $denominator = $this->normal->dot($direction);
        if ($denominator == 0) {
            if ($this->distanceToPoint($line->start) == 0) {
                return $target->copy($line->start);
            }
            return null; // Uncertain if this is the correct way to handle this case.
        }
        $t = - ($line->start->dot($this->normal) + $this->constant) / $denominator;
        if ($t < 0 || $t > 1) {
            return null;
        }
        return $target->copy($direction)->multiplyScalar($t)->add($line->start);
    }

    public function intersectsLine($line)
    {
        $startSign = $this->distanceToPoint($line->start);
        $endSign = $this->distanceToPoint($line->end);
        return ($startSign < 0 && $endSign > 0) || ($endSign < 0 && $startSign > 0);
    }

    public function intersectsBox($box)
    {
        return $box->intersectsPlane($this);
    }

    public function intersectsSphere($sphere)
    {
        return $sphere->intersectsPlane($this);
    }

    public function coplanarPoint($target)
    {
        return $target->copy($this->normal)->multiplyScalar(-$this->constant);
    }

    public function applyMatrix4($matrix, $optionalNormalMatrix = null)
    {
        $vector = new Vector3();
        $normalMatrix = new Matrix3();
        $normalMatrix = $optionalNormalMatrix ?? $normalMatrix->getNormalMatrix($matrix);
        $referencePoint = $this->coplanarPoint($vector)->applyMatrix4($matrix);
        $normal = $this->normal->applyMatrix3($normalMatrix)->normalize();
        $this->constant = -$referencePoint->dot($normal);
        return $this;
    }

    public function translate($offset)
    {
        $this->constant -= $offset->dot($this->normal);
        return $this;
    }

    public function equals($plane)
    {
        return $plane->normal->equals($this->normal) && ($plane->constant === $this->constant);
    }

    public function clone()
    {
        return new self($this->normal->clone(), $this->constant);
    }
}

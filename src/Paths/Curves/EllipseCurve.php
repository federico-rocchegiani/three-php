<?php

namespace ThreePHP\Paths\Curves;

use ThreePHP\Math\Vector2;
use ThreePHP\Paths\Curve;

class EllipseCurve extends Curve
{
    public $aX;
    public $aY;
    public $xRadius;
    public $yRadius;
    public $aStartAngle;
    public $aEndAngle;
    public $aClockwise;
    public $aRotation;
    public $isEllipseCurve;

    public function __construct($aX = 0, $aY = 0, $xRadius = 1, $yRadius = 1, $aStartAngle = 0, $aEndAngle = M_PI * 2, $aClockwise = false, $aRotation = 0)
    {
        parent::__construct();
        $this->isEllipseCurve = true;
        $this->type = 'EllipseCurve';
        $this->aX = $aX;
        $this->aY = $aY;
        $this->xRadius = $xRadius;
        $this->yRadius = $yRadius;
        $this->aStartAngle = $aStartAngle;
        $this->aEndAngle = $aEndAngle;
        $this->aClockwise = $aClockwise;
        $this->aRotation = $aRotation;
    }

    public function getPoint($t, $optionalTarget = null)
    {
        $point = $optionalTarget ?? new Vector2();
        $twoPi = M_PI * 2;
        $deltaAngle = $this->aEndAngle - $this->aStartAngle;
        $samePoints = abs($deltaAngle) < PHP_FLOAT_EPSILON;

        while ($deltaAngle < 0) {
            $deltaAngle += $twoPi;
        }
        while ($deltaAngle > $twoPi) {
            $deltaAngle -= $twoPi;
        }

        if ($deltaAngle < PHP_FLOAT_EPSILON) {
            if ($samePoints) {
                $deltaAngle = 0;
            } else {
                $deltaAngle = $twoPi;
            }
        }

        if ($this->aClockwise === true && !$samePoints) {
            if ($deltaAngle === $twoPi) {
                $deltaAngle = -$twoPi;
            } else {
                $deltaAngle = $deltaAngle - $twoPi;
            }
        }

        $angle = $this->aStartAngle + $t * $deltaAngle;
        $x = $this->aX + $this->xRadius * cos($angle);
        $y = $this->aY + $this->yRadius * sin($angle);

        if ($this->aRotation !== 0) {
            $cos = cos($this->aRotation);
            $sin = sin($this->aRotation);
            $tx = $x - $this->aX;
            $ty = $y - $this->aY;
            $x = $tx * $cos - $ty * $sin + $this->aX;
            $y = $tx * $sin + $ty * $cos + $this->aY;
        }

        return $point->set($x, $y);
    }

    public function copy($source)
    {
        parent::copy($source);
        $this->aX = $source->aX;
        $this->aY = $source->aY;
        $this->xRadius = $source->xRadius;
        $this->yRadius = $source->yRadius;
        $this->aStartAngle = $source->aStartAngle;
        $this->aEndAngle = $source->aEndAngle;
        $this->aClockwise = $source->aClockwise;
        $this->aRotation = $source->aRotation;
        return $this;
    }

    public function toJSON()
    {
        $data = parent::toJSON();
        $data['aX'] = $this->aX;
        $data['aY'] = $this->aY;
        $data['xRadius'] = $this->xRadius;
        $data['yRadius'] = $this->yRadius;
        $data['aStartAngle'] = $this->aStartAngle;
        $data['aEndAngle'] = $this->aEndAngle;
        $data['aClockwise'] = $this->aClockwise;
        $data['aRotation'] = $this->aRotation;
        return $data;
    }

    public function fromJSON($json)
    {
        parent::fromJSON($json);
        $this->aX = $json['aX'];
        $this->aY = $json['aY'];
        $this->xRadius = $json['xRadius'];
        $this->yRadius = $json['yRadius'];
        $this->aStartAngle = $json['aStartAngle'];
        $this->aEndAngle = $json['aEndAngle'];
        $this->aClockwise = $json['aClockwise'];
        $this->aRotation = $json['aRotation'];
        return $this;
    }
}

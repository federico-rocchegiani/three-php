<?php

namespace ThreePHP\Paths;

use ThreePHP\Paths\Curves\CubicBezierCurve;
use ThreePHP\Paths\Curves\EllipseCurve;
use ThreePHP\Paths\Curves\LineCurve;
use ThreePHP\Paths\Curves\QuadraticBezierCurve;
use ThreePHP\Paths\Curves\SplineCurve;
use ThreePHP\Math\Vector2;

class Path extends CurvePath
{

    public $currentPoint;

    public function __construct($points = [])
    {
        parent::__construct();
        $this->type = 'Path';
        $this->currentPoint = new Vector2();
        if ($points) {
            $this->setFromPoints($points);
        }
    }

    public function setFromPoints($points)
    {
        $this->moveTo($points[0]->x, $points[0]->y);
        $count = count($points);
        for ($i = 1; $i < $count; $i++) {
            $this->lineTo($points[$i]->x, $points[$i]->y);
        }
        return $this;
    }

    public function moveTo($x, $y)
    {
        $this->currentPoint->set($x, $y);
        return $this;
    }

    public function lineTo($x, $y)
    {
        $curve = new LineCurve($this->currentPoint->clone(), new Vector2($x, $y));
        $this->curves[] = $curve;
        $this->currentPoint->set($x, $y);
        return $this;
    }

    public function quadraticCurveTo($aCPx, $aCPy, $aX, $aY)
    {
        $curve = new QuadraticBezierCurve(
            $this->currentPoint->clone(),
            new Vector2($aCPx, $aCPy),
            new Vector2($aX, $aY)
        );
        $this->curves[] = $curve;
        $this->currentPoint->set($aX, $aY);
        return $this;
    }

    public function bezierCurveTo($aCP1x, $aCP1y, $aCP2x, $aCP2y, $aX, $aY)
    {
        $curve = new CubicBezierCurve(
            $this->currentPoint->clone(),
            new Vector2($aCP1x, $aCP1y),
            new Vector2($aCP2x, $aCP2y),
            new Vector2($aX, $aY)
        );
        $this->curves[] = $curve;
        $this->currentPoint->set($aX, $aY);
        return $this;
    }

    public function splineThru($pts)
    {
        $npts = [$this->currentPoint->clone()];
        $npts = array_merge($npts, $pts);
        $curve = new SplineCurve($npts);
        $this->curves[] = $curve;
        $this->currentPoint->copy($pts[count($pts) - 1]);
        return $this;
    }

    public function arc($aX, $aY, $aRadius, $aStartAngle, $aEndAngle, $aClockwise)
    {
        $x0 = $this->currentPoint->x;
        $y0 = $this->currentPoint->y;
        $this->absarc($aX + $x0, $aY + $y0, $aRadius, $aStartAngle, $aEndAngle, $aClockwise);
        return $this;
    }

    public function absarc($aX, $aY, $aRadius, $aStartAngle, $aEndAngle, $aClockwise)
    {
        $this->absellipse($aX, $aY, $aRadius, $aRadius, $aStartAngle, $aEndAngle, $aClockwise);
        return $this;
    }

    public function ellipse($aX, $aY, $xRadius, $yRadius, $aStartAngle, $aEndAngle, $aClockwise, $aRotation)
    {
        $x0 = $this->currentPoint->x;
        $y0 = $this->currentPoint->y;
        $this->absellipse($aX + $x0, $aY + $y0, $xRadius, $yRadius, $aStartAngle, $aEndAngle, $aClockwise, $aRotation);
        return $this;
    }

    public function absellipse($aX, $aY, $xRadius, $yRadius, $aStartAngle, $aEndAngle, $aClockwise, $aRotation = null)
    {
        $curve = new EllipseCurve($aX, $aY, $xRadius, $yRadius, $aStartAngle, $aEndAngle, $aClockwise, $aRotation);
        if (count($this->curves) > 0) {
            $firstPoint = $curve->getPoint(0);
            if (!$firstPoint->equals($this->currentPoint)) {
                $this->lineTo($firstPoint->x, $firstPoint->y);
            }
        }
        $this->curves[] = $curve;
        $lastPoint = $curve->getPoint(1);
        $this->currentPoint->copy($lastPoint);
        return $this;
    }

    public function copy($source)
    {
        parent::copy($source);
        $this->currentPoint->copy($source->currentPoint);
        return $this;
    }

    public function toJSON()
    {
        $data = parent::toJSON();
        $data['currentPoint'] = $this->currentPoint->toArray();
        return $data;
    }

    public function fromJSON($json)
    {
        parent::fromJSON($json);
        $this->currentPoint->fromArray($json['currentPoint']);
        return $this;
    }
}

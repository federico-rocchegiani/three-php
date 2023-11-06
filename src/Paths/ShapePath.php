<?php

namespace ThreePHP\Paths;

use ThreePHP\Math\Color;

class ShapePath
{
    public $type;
    public $color;
    public $subPaths;
    public ?Path $currentPath;

    public function __construct()
    {
        $this->type = 'ShapePath';
        $this->color = new Color();
        $this->subPaths = [];
        $this->currentPath = null;
    }

    public function moveTo($x, $y)
    {
        $this->currentPath = new Path();
        $this->subPaths[] = $this->currentPath;
        $this->currentPath->moveTo($x, $y);
        return $this;
    }

    public function lineTo($x, $y)
    {
        $this->currentPath->lineTo($x, $y);
        return $this;
    }

    public function quadraticCurveTo($aCPx, $aCPy, $aX, $aY)
    {
        $this->currentPath->quadraticCurveTo($aCPx, $aCPy, $aX, $aY);
        return $this;
    }

    public function bezierCurveTo($aCP1x, $aCP1y, $aCP2x, $aCP2y, $aX, $aY)
    {
        $this->currentPath->bezierCurveTo($aCP1x, $aCP1y, $aCP2x, $aCP2y, $aX, $aY);
        return $this;
    }

    public function splineThru($pts)
    {
        $this->currentPath->splineThru($pts);
        return $this;
    }

    private function toShapesNoHoles($inSubpaths)
    {
        $shapes = [];
        foreach ($inSubpaths as $tmpPath) {
            $tmpShape = new Shape();
            $tmpShape->curves = $tmpPath->curves;
            $shapes[] = $tmpShape;
        }
        return $shapes;
    }

    private function isPointInsidePolygon($inPt, $inPolygon)
    {
        $polyLen = count($inPolygon);
        $inside = false;

        for ($p = $polyLen - 1, $q = 0; $q < $polyLen; $p = $q++) {
            $edgeLowPt = $inPolygon[$p];
            $edgeHighPt = $inPolygon[$q];
            $edgeDx = $edgeHighPt->x - $edgeLowPt->x;
            $edgeDy = $edgeHighPt->y - $edgeLowPt->y;

            if (abs($edgeDy) > PHP_FLOAT_EPSILON) {
                if ($edgeDy < 0) {
                    $edgeLowPt = $inPolygon[$q];
                    $edgeDx = -$edgeDx;
                    $edgeHighPt = $inPolygon[$p];
                    $edgeDy = -$edgeDy;
                }

                if ($inPt->y < $edgeLowPt->y || $inPt->y > $edgeHighPt->y) {
                    continue;
                }

                if ($inPt->y === $edgeLowPt->y) {
                    if ($inPt->x === $edgeLowPt->x) {
                        return true; // inPt is on contour
                    }
                } else {
                    $perpEdge = $edgeDy * ($inPt->x - $edgeLowPt->x) - $edgeDx * ($inPt->y - $edgeLowPt->y);
                    if ($perpEdge === 0) {
                        return true; // inPt is on contour
                    }
                    if ($perpEdge < 0) {
                        continue;
                    }
                    $inside = !$inside; // true intersection left of inPt
                }
            } else {
                if ($inPt->y !== $edgeLowPt->y) {
                    continue;
                }

                if (($edgeHighPt->x <= $inPt->x && $inPt->x <= $edgeLowPt->x) || ($edgeLowPt->x <= $inPt->x && $inPt->x <= $edgeHighPt->x)) {
                    return true; // inPt: Point on contour
                }
            }
        }

        return $inside;
    }

    public function toShapes($isCCW = null)
    {

        #$isClockWise = ShapeUtils::isClockWise;
        $subPaths = $this->subPaths;

        if (count($subPaths) === 0) {
            return [];
        }

        $solid = null;
        $tmpPath = null;
        $tmpShape = null;
        $shapes = [];

        if (count($subPaths) === 1) {
            $tmpPath = $subPaths[0];
            $tmpShape = new Shape();
            $tmpShape->curves = $tmpPath->curves;
            $shapes[] = $tmpShape;
            return $shapes;
        }

        $holesFirst = !ShapeUtils::isClockWise($subPaths[0]->getPoints());
        $holesFirst = $isCCW ? !$holesFirst : $holesFirst;

        $betterShapeHoles = [];
        $newShapes = [];
        $newShapeHoles = [];
        $mainIdx = 0;
        $tmpPoints = [];

        $newShapes[$mainIdx] = null;
        $newShapeHoles[$mainIdx] = [];

        foreach ($subPaths as $i => $tmpPath) {
            $tmpPoints = $tmpPath->getPoints();
            $solid = ShapeUtils::isClockWise($tmpPoints);
            $solid = $isCCW ? !$solid : $solid;

            if ($solid) {
                if (!$holesFirst && $newShapes[$mainIdx] !== null) {
                    $mainIdx++;
                }

                $newShapes[$mainIdx] = ['s' => new Shape(), 'p' => $tmpPoints];
                $newShapes[$mainIdx]['s']->curves = $tmpPath->curves;

                if ($holesFirst) {
                    $mainIdx++;
                }

                $newShapeHoles[$mainIdx] = [];
            } else {
                $newShapeHoles[$mainIdx][] = ['h' => $tmpPath, 'p' => $tmpPoints[0]];
            }
        }

        if ($newShapes[0] === null) {
            return $this->toShapesNoHoles($subPaths);
        }

        if (count($newShapes) > 1) {
            $ambiguous = false;
            $toChange = 0;

            for ($sIdx = 0, $sLen = count($newShapes); $sIdx < $sLen; $sIdx++) {
                $betterShapeHoles[$sIdx] = [];
            }

            for ($sIdx = 0, $sLen = count($newShapes); $sIdx < $sLen; $sIdx++) {
                $sho = $newShapeHoles[$sIdx];

                foreach ($sho as $hIdx => $ho) {
                    $hole_unassigned = true;

                    for ($s2Idx = 0; $s2Idx < count($newShapes); $s2Idx++) {
                        if ($this->isPointInsidePolygon($ho['p'], $newShapes[$s2Idx]['p'])) {
                            if ($sIdx !== $s2Idx) {
                                $toChange++;
                            }

                            if ($hole_unassigned) {
                                $hole_unassigned = false;
                                $betterShapeHoles[$s2Idx][] = $ho;
                            } else {
                                $ambiguous = true;
                            }
                        }
                    }

                    if ($hole_unassigned) {
                        $betterShapeHoles[$sIdx][] = $ho;
                    }
                }
            }

            if ($toChange > 0 && !$ambiguous) {
                $newShapeHoles = $betterShapeHoles;
            }
        }

        $tmpHoles = null;

        for ($i = 0, $il = count($newShapes); $i < $il; $i++) {
            $tmpShape = $newShapes[$i]['s'];
            $shapes[] = $tmpShape;
            $tmpHoles = $newShapeHoles[$i];

            foreach ($tmpHoles as $j => $ho) {
                $tmpShape->holes[] = $ho['h'];
            }
        }

        return $shapes;
    }
}

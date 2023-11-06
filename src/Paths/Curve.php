<?php

namespace ThreePHP\Paths;

use ThreePHP\Math\MathUtils;
use ThreePHP\Math\Matrix4;
use ThreePHP\Math\Vector2;
use ThreePHP\Math\Vector3;

abstract class Curve
{
    public $type;
    public $arcLengthDivisions;
    protected $needsUpdate;
    protected $cacheArcLengths;

    public function __construct()
    {
        $this->type = 'Curve';
        $this->arcLengthDivisions = 200;
    }

    abstract public function getPoint($t, $optionalTarget = null);

    public function getPointAt($u, $optionalTarget = null)
    {
        $t = $this->getUtoTmapping($u);
        return $this->getPoint($t, $optionalTarget);
    }

    public function getPoints($divisions = 5)
    {
        $points = [];
        for ($d = 0; $d <= $divisions; $d++) {
            $points[] = $this->getPoint($d / $divisions);
        }
        return $points;
    }

    public function getSpacedPoints($divisions = 5)
    {
        $points = [];
        for ($d = 0; $d <= $divisions; $d++) {
            $points[] = $this->getPointAt($d / $divisions);
        }
        return $points;
    }

    public function getLength()
    {
        $lengths = $this->getLengths();
        return $lengths[count($lengths) - 1];
    }

    public function getLengths($divisions = null)
    {
        if ($divisions === null) {
            $divisions = $this->arcLengthDivisions;
        }
        if (
            $this->cacheArcLengths &&
            (count($this->cacheArcLengths) === $divisions + 1) &&
            !$this->needsUpdate
        ) {
            return $this->cacheArcLengths;
        }

        $this->needsUpdate = false;
        $cache = [];
        $current = $last = $this->getPoint(0);
        $sum = 0;
        array_push($cache, 0);

        for ($p = 1; $p <= $divisions; $p++) {
            $current = $this->getPoint($p / $divisions);
            $sum += $current->distanceTo($last);
            array_push($cache, $sum);
            $last = $current;
        }

        $this->cacheArcLengths = $cache;

        return $cache; // { sums: cache, sum: sum }; Sum is in the last element.
    }


    public function updateArcLengths()
    {
        $this->needsUpdate = true;
        $this->getLengths();
    }

    public function getUtoTmapping($u, $distance = null)
    {
        $arcLengths = $this->getLengths();
        $i = 0;
        $il = count($arcLengths);
        if ($distance !== null) {
            $targetArcLength = $distance;
        } else {
            $targetArcLength = $u * $arcLengths[$il - 1];
        }
        $low = 0;
        $high = $il - 1;
        $comparison = 0;
        while ($low <= $high) {
            $i = floor($low + ($high - $low) / 2);
            $comparison = $arcLengths[$i] - $targetArcLength;
            if ($comparison < 0) {
                $low = $i + 1;
            } elseif ($comparison > 0) {
                $high = $i - 1;
            } else {
                $high = $i;
                break;
            }
        }
        $i = $high;
        if ($arcLengths[$i] === $targetArcLength) {
            return $i / ($il - 1);
        }
        $lengthBefore = $arcLengths[$i];
        $lengthAfter = $arcLengths[$i + 1];
        $segmentLength = $lengthAfter - $lengthBefore;
        $segmentFraction = ($targetArcLength - $lengthBefore) / $segmentLength;
        $t = ($i + $segmentFraction) / ($il - 1);
        return $t;
    }

    public function getTangent($t, $optionalTarget = null)
    {
        $delta = 0.0001;
        $t1 = $t - $delta;
        $t2 = $t + $delta;
        if ($t1 < 0) $t1 = 0;
        if ($t2 > 1) $t2 = 1;
        $pt1 = $this->getPoint($t1);
        $pt2 = $this->getPoint($t2);
        $tangent = $optionalTarget ?: ($pt1 instanceof Vector2 ? new Vector2() : new Vector3());
        $tangent->copy($pt2)->sub($pt1)->normalize();
        return $tangent;
    }

    public function getTangentAt($u, $optionalTarget = null)
    {
        $t = $this->getUtoTmapping($u);
        return $this->getTangent($t, $optionalTarget);
    }

    function computeFrenetFrames($segments, $closed)
    {
        // Vedere http://www.cs.indiana.edu/pub/techreports/TR425.pdf
        $normal = new Vector3();
        $tangents = [];
        $normals = [];
        $binormals = [];
        $vec = new Vector3();
        $mat = new Matrix4();

        // Calcolare i vettori tangenti per ogni segmento della curva
        for ($i = 0; $i <= $segments; $i++) {
            $u = $i / $segments;
            $tangents[$i] = $this->getTangentAt($u, new Vector3());
        }

        // Selezionare un vettore normale iniziale perpendicolare al primo vettore tangente,
        // e nella direzione del minimo componente xyz del tangente
        $normals[0] = new Vector3();
        $binormals[0] = new Vector3();
        $min = PHP_FLOAT_MAX;
        $tx = abs($tangents[0]->x);
        $ty = abs($tangents[0]->y);
        $tz = abs($tangents[0]->z);

        if ($tx <= $min) {
            $min = $tx;
            $normal->set(1, 0, 0);
        }

        if ($ty <= $min) {
            $min = $ty;
            $normal->set(0, 1, 0);
        }

        if ($tz <= $min) {
            $normal->set(0, 0, 1);
        }

        $vec->crossVectors($tangents[0], $normal)->normalize();
        $normals[0]->crossVectors($tangents[0], $vec);
        $binormals[0]->crossVectors($tangents[0], $normals[0]);

        // Calcolare i vettori normali e binormali lentamente variabili per ogni segmento della curva
        for ($i = 1; $i <= $segments; $i++) {
            $normals[$i] = $normals[$i - 1]->clone();
            $binormals[$i] = $binormals[$i - 1]->clone();
            $vec->crossVectors($tangents[$i - 1], $tangents[$i]);

            if ($vec->length() > PHP_FLOAT_EPSILON) {
                $vec->normalize();
                $theta = acos(MathUtils::clamp($tangents[$i - 1]->dot($tangents[$i]), -1, 1)); // Limitazione per errori in virgola mobile
                $normals[$i]->applyMatrix4($mat->makeRotationAxis($vec, $theta));
            }

            $binormals[$i]->crossVectors($tangents[$i], $normals[$i]);
        }

        // Se la curva è chiusa, elabora i vettori in modo che i primi e gli ultimi vettori normali siano uguali
        if ($closed === true) {
            $theta = acos(MathUtils::clamp($normals[0]->dot($normals[$segments]), -1, 1));
            $theta /= $segments;

            if ($tangents[0]->dot($vec->crossVectors($normals[0], $normals[$segments])) > 0) {
                $theta = -$theta;
            }

            for ($i = 1; $i <= $segments; $i++) {
                // Torsione un po'...
                $normals[$i]->applyMatrix4($mat->makeRotationAxis($tangents[$i], $theta * $i));
                $binormals[$i]->crossVectors($tangents[$i], $normals[$i]);
            }
        }

        return [
            'tangents' => $tangents,
            'normals' => $normals,
            'binormals' => $binormals
        ];
    }


    public function clone()
    {
        return new static();
    }

    public function copy($source)
    {
        $this->arcLengthDivisions = $source->arcLengthDivisions;
        return $this;
    }

    public function toJSON()
    {
        $data = [
            'metadata' => [
                'version' => 4.5,
                'type' => 'Curve',
                'generator' => 'Curve.toJSON'
            ],
            'arcLengthDivisions' => $this->arcLengthDivisions,
            'type' => $this->type
        ];
        return $data;
    }

    public function fromJSON($json)
    {
        $this->arcLengthDivisions = $json['arcLengthDivisions'];
        return $this;
    }
}

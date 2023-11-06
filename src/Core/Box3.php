<?php

namespace ThreePHP\Core;

use ThreePHP\Math\Vector3;
use Vector;

class Box3
{
    public $isBox3 = true;
    public $min;
    public $max;

    public function __construct(Vector3 $min = new Vector3(+INF, +INF, +INF), Vector3 $max = new Vector3(-INF, -INF, -INF))
    {
        if ($min === null) {
            $min = new Vector3(INF, INF, INF);
        }
        if ($max === null) {
            $max = new Vector3(-INF, -INF, -INF);
        }
        $this->min = $min;
        $this->max = $max;
    }

    public function set(Vector3 $min, Vector3 $max)
    {
        $this->min->copy($min);
        $this->max->copy($max);
        return $this;
    }

    public function setFromArray(array $array)
    {
        $minX = INF;
        $minY = INF;
        $minZ = INF;
        $maxX = -INF;
        $maxY = -INF;
        $maxZ = -INF;

        for ($i = 0, $l = count($array); $i < $l; $i += 3) {
            $x = $array[$i];
            $y = $array[$i + 1];
            $z = $array[$i + 2];

            if ($x < $minX) $minX = $x;
            if ($y < $minY) $minY = $y;
            if ($z < $minZ) $minZ = $z;
            if ($x > $maxX) $maxX = $x;
            if ($y > $maxY) $maxY = $y;
            if ($z > $maxZ) $maxZ = $z;
        }

        $this->min->set($minX, $minY, $minZ);
        $this->max->set($maxX, $maxY, $maxZ);
        return $this;
    }

    public function setFromBufferAttribute(BufferAttribute $attribute)
    {
        $minX = INF;
        $minY = INF;
        $minZ = INF;
        $maxX = -INF;
        $maxY = -INF;
        $maxZ = -INF;

        for ($i = 0, $l = $attribute->count; $i < $l; $i++) {
            $x = $attribute->getX($i);
            $y = $attribute->getY($i);
            $z = $attribute->getZ($i);

            if ($x < $minX) $minX = $x;
            if ($y < $minY) $minY = $y;
            if ($z < $minZ) $minZ = $z;
            if ($x > $maxX) $maxX = $x;
            if ($y > $maxY) $maxY = $y;
            if ($z > $maxZ) $maxZ = $z;
        }

        $this->min->set($minX, $minY, $minZ);
        $this->max->set($maxX, $maxY, $maxZ);
        return $this;
    }

    public function setFromPoints(array $points)
    {
        $this->makeEmpty();

        foreach ($points as $point) {
            $this->expandByPoint($point);
        }

        return $this;
    }

    public function setFromCenterAndSize(Vector3 $center, Vector3 $size)
    {
        $halfSize = (clone $size)->multiplyScalar(0.5);
        $this->min->copy($center)->sub($halfSize);
        $this->max->copy($center)->add($halfSize);
        return $this;
    }

    public function setFromObject($object, $precise = false)
    {
        $this->makeEmpty();
        return $this->expandByObject($object, $precise);
    }

    public function clone()
    {
        return new self($this->min->clone(), $this->max->clone());
    }

    public function copy(Box3 $box)
    {
        $this->min->copy($box->min);
        $this->max->copy($box->max);
        return $this;
    }

    public function makeEmpty()
    {
        $this->min->x = $this->min->y = $this->min->z = INF;
        $this->max->x = $this->max->y = $this->max->z = -INF;
        return $this;
    }

    public function isEmpty()
    {
        return ($this->max->x < $this->min->x) || ($this->max->y < $this->min->y) || ($this->max->z < $this->min->z);
    }

    public function getCenter(Vector3 $target)
    {
        return $this->isEmpty() ? $target->set(0, 0, 0) : $target->addVectors($this->min, $this->max)->multiplyScalar(0.5);
    }

    public function getSize(Vector3 $target)
    {
        return $this->isEmpty() ? $target->set(0, 0, 0) : $target->subVectors($this->max, $this->min);
    }

    public function expandByPoint(Vector3 $point)
    {
        $this->min->min($point);
        $this->max->max($point);
        return $this;
    }

    public function expandByVector(Vector3 $vector)
    {
        $this->min->sub($vector);
        $this->max->add($vector);
        return $this;
    }

    public function expandByScalar($scalar)
    {
        $this->min->addScalar(-$scalar);
        $this->max->addScalar($scalar);
        return $this;
    }

    public function expandByObject($object, $precise = false)
    {
        $object->updateWorldMatrix(false, false);
        $geometry = $object->geometry;

        if ($geometry !== null) {
            if ($precise && $geometry->attributes !== null && $geometry->attributes->position !== null) {
                $position = $geometry->attributes->position;

                for ($i = 0, $l = count($position); $i < $l; $i++) {
                    $vector = $position->fromBufferAttribute($i)->applyMatrix4($object->matrixWorld);
                    $this->expandByPoint($vector);
                }
            } else {
                if ($geometry->boundingBox === null) {
                    $geometry->computeBoundingBox();
                }
                $box = $geometry->boundingBox->copy();
                $box->applyMatrix4($object->matrixWorld);
                $this->union($box);
            }
        }

        $children = $object->children;

        for ($i = 0, $l = count($children); $i < $l; $i++) {
            $this->expandByObject($children[$i], $precise);
        }

        return $this;
    }

    public function containsPoint(Vector3 $point)
    {
        return $point->x < $this->min->x || $point->x > $this->max->x ||
            $point->y < $this->min->y || $point->y > $this->max->y ||
            $point->z < $this->min->z || $point->z > $this->max->z ? false : true;
    }

    public function containsBox($box)
    {
        return $this->min->x <= $box->min->x && $box->max->x <= $this->max->x &&
            $this->min->y <= $box->min->y && $box->max->y <= $this->max->y &&
            $this->min->z <= $box->min->z && $box->max->z <= $this->max->z;
    }

    public function getParameter(Vector3 $point, Vector3 $target)
    {
        return $target->set(
            ($point->x - $this->min->x) / ($this->max->x - $this->min->x),
            ($point->y - $this->min->y) / ($this->max->y - $this->min->y),
            ($point->z - $this->min->z) / ($this->max->z - $this->min->z)
        );
    }

    public function intersectsBox($box)
    {
        return $box->max->x < $this->min->x || $box->min->x > $this->max->x ||
            $box->max->y < $this->min->y || $box->min->y > $this->max->y ||
            $box->max->z < $this->min->z || $box->min->z > $this->max->z ? false : true;
    }

    public function intersectsSphere(Sphere $sphere)
    {
        $vector = new Vector3();
        $this->clampPoint($sphere->center, $vector);
        return $vector->distanceToSquared($sphere->center) <= ($sphere->radius * $sphere->radius);
    }

    public function intersectsPlane(Plane $plane)
    {
        $min = $plane->normal->x > 0 ?
            $plane->normal->x * $this->min->x :
            $plane->normal->x * $this->max->x;

        $max = $plane->normal->y > 0 ?
            $min + $plane->normal->y * $this->min->y :
            $min + $plane->normal->y * $this->max->y;

        $min += $plane->normal->z > 0 ?
            $plane->normal->z * $this->min->z :
            $plane->normal->z * $this->max->z;

        return ($min <= -$plane->constant && $max >= -$plane->constant);
    }

    public function intersectsTriangle($triangle)
    {
        if ($this->isEmpty()) {
            return false;
        }
        // Calcola il centro e le estensioni del box
        $center = new Vector3();
        $this->getCenter($center);
        $extents = $this->max->sub($center);

        // Trasla il triangolo all'origine del box
        $v0 = $triangle->a->sub($center);
        $v1 = $triangle->b->sub($center);
        $v2 = $triangle->c->sub($center);

        // Calcola i vettori dei lati del triangolo
        $f0 = $v1->sub($v0);
        $f1 = $v2->sub($v1);
        $f2 = $v0->sub($v2);

        // Test sugli assi dati dalle combinazioni di prodotti vettoriali tra i lati del triangolo e i lati del box
        $axes = [
            0, -$f0->z, $f0->y, 0, -$f1->z, $f1->y, 0, -$f2->z, $f2->y,
            $f0->z, 0, -$f0->x, $f1->z, 0, -$f1->x, $f2->z, 0, -$f2->x,
            -$f0->y, $f0->x, 0, -$f1->y, $f1->x, 0, -$f2->y, $f2->x, 0
        ];

        if (!$this->satForAxes($axes, $v0, $v1, $v2, $extents)) {
            return false;
        }

        // Test sulle 3 normali del box
        $axes = [1, 0, 0, 0, 1, 0, 0, 0, 1];
        if (!$this->satForAxes($axes, $v0, $v1, $v2, $extents)) {
            return false;
        }

        // Infine, test sulla normale del triangolo
        // Usa i vettori dei lati del triangolo già esistenti
        $triangleNormal = $f0->cross($f1);
        $axes = [$triangleNormal->x, $triangleNormal->y, $triangleNormal->z];
        return $this->satForAxes($axes, $v0, $v1, $v2, $extents);
    }

    public function clampPoint($point, $target)
    {
        return $target->copy($point)->clamp($this->min, $this->max);
    }

    public function distanceToPoint($point)
    {
        $clampedPoint = $point->clone()->clamp($this->min, $this->max);
        return $clampedPoint->sub($point)->length();
    }

    public function getBoundingSphere($target)
    {
        $this->getCenter($target->center);
        $vector = new Vector3();
        $target->radius = $this->getSize($vector)->length() * 0.5;
        return $target;
    }

    public function intersect($box)
    {
        $this->min->max($box->min);
        $this->max->min($box->max);

        // Assicura che, se non c'è sovrapposizione, il risultato sia completamente vuoto, non leggermente vuoto con valori non-inf/+inf che causeranno successivi intersects a restituire erroneamente valori validi.
        if ($this->isEmpty()) {
            $this->makeEmpty();
        }
        return $this;
    }

    public function union($box)
    {
        $this->min->min($box->min);
        $this->max->max($box->max);
        return $this;
    }

    public function applyMatrix4($matrix)
    {
        // La trasformazione di un box vuoto è un box vuoto.
        if ($this->isEmpty()) {
            return $this;
        }

        // NOTA: Sto usando un modello binario per specificare tutte le 2^3 combinazioni qui sotto
        $points = [
            $this->min->clone(),
            $this->min->clone(),
            $this->min->clone(),
            $this->min->clone(),
            $this->max->clone(),
            $this->max->clone(),
            $this->max->clone(),
            $this->max->clone()
        ];

        $points[0]->applyMatrix4($matrix); // 000
        $points[1]->set($this->min->x, $this->min->y, $this->max->z)->applyMatrix4($matrix); // 001
        $points[2]->set($this->min->x, $this->max->y, $this->min->z)->applyMatrix4($matrix); // 010
        $points[3]->set($this->min->x, $this->max->y, $this->max->z)->applyMatrix4($matrix); // 011
        $points[4]->set($this->max->x, $this->min->y, $this->min->z)->applyMatrix4($matrix); // 100
        $points[5]->set($this->max->x, $this->min->y, $this->max->z)->applyMatrix4($matrix); // 101
        $points[6]->set($this->max->x, $this->max->y, $this->min->z)->applyMatrix4($matrix); // 110
        $points[7]->set($this->max->x, $this->max->y, $this->max->z)->applyMatrix4($matrix); // 111

        $this->setFromPoints($points);
        return $this;
    }

    public function translate($offset)
    {
        $this->min->add($offset);
        $this->max->add($offset);
        return $this;
    }

    public function equals($box)
    {
        return $box->min->equals($this->min) && $box->max->equals($this->max);
    }

    private function satForAxes($axes, $v0, $v1, $v2, $extents)
    {
        $numAxes = count($axes);
        $j = $numAxes - 3;

        for ($i = 0; $i <= $j; $i += 3) {
            $testAxis = new Vector3($axes[$i], $axes[$i + 1], $axes[$i + 2]);

            // Proietta l'aabb sull'asse di separazione
            $r = $extents->x * abs($testAxis->x) + $extents->y * abs($testAxis->y) + $extents->z * abs($testAxis->z);

            // Proietta tutti e 3 i vertici del triangolo sull'asse di separazione
            $p0 = $v0->dot($testAxis);
            $p1 = $v1->dot($testAxis);
            $p2 = $v2->dot($testAxis);

            // Test effettivo, fondamentalmente verifica se uno dei punti più estremi del triangolo interseca r
            if (max(-max($p0, $p1, $p2), min($p0, $p1, $p2)) > $r) {
                // I punti del triangolo proiettato sono al di fuori della semilunghezza proiettata dell'aabb
                // l'asse è separante e possiamo uscire
                return false;
            }
        }

        return true;
    }
}

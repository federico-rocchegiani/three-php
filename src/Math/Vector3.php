<?php

namespace ThreePHP\Math;

use Exception;

class Vector3 implements \Iterator
{
    public $x;
    public $y;
    public $z;

    private $position = 0;
    private $quaternion;

    public function __construct($x = 0, $y = 0, $z = 0)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;

        $this->quaternion = new Quaternion();
    }

    public function set($x, $y, $z)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;

        return $this;
    }

    public function setScalar($scalar)
    {
        $this->x = $scalar;
        $this->y = $scalar;
        $this->z = $scalar;

        return $this;
    }

    public function setX($x)
    {
        $this->x = $x;

        return $this;
    }

    public function setY($y)
    {
        $this->y = $y;

        return $this;
    }

    public function setZ($z)
    {
        $this->z = $z;

        return $this;
    }

    public function setComponent($index, $value)
    {
        switch ($index) {

            case 0:
                $this->x = $value;
                break;
            case 1:
                $this->y = $value;
                break;
            case 2:
                $this->z = $value;
                break;
            default:
                throw new Exception('index is out of range: ' . $index);
        }

        return $this;
    }

    public function getComponent($index)
    {
        switch ($index) {

            case 0:
                return $this->x;
            case 1:
                return $this->y;
            case 2:
                return $this->z;
            default:
                throw new Exception('index is out of range: ' . $index);
        }
    }

    public function clone()
    {
        return new static($this->x, $this->y, $this->z);
    }

    public function copy($vector)
    {
        $this->x = $vector->x;
        $this->y = $vector->y;
        $this->z = $vector->z;

        return $this;
    }

    public function add($vector)
    {
        $this->x += $vector->x;
        $this->y += $vector->y;
        $this->z += $vector->z;

        return $this;
    }

    public function addScalar($scalar)
    {
        $this->x += $scalar;
        $this->y += $scalar;
        $this->z += $scalar;

        return $this;
    }

    public function addVectors($a, $b)
    {
        $this->x = $a->x + $b->x;
        $this->y = $a->y + $b->y;
        $this->z = $a->z + $b->z;

        return $this;
    }

    public function addScaledVector($vector, $scale)
    {
        $this->x += $vector->x * $scale;
        $this->y += $vector->y * $scale;
        $this->z += $vector->z * $scale;

        return $this;
    }

    public function sub($vector): static
    {
        $this->x -= $vector->x;
        $this->y -= $vector->y;
        $this->z -= $vector->z;

        return $this;
    }

    public function subScalar($scalar)
    {
        $this->x -= $scalar;
        $this->y -= $scalar;
        $this->z -= $scalar;

        return $this;
    }

    public function subVectors($a, $b)
    {
        $this->x = $a->x - $b->x;
        $this->y = $a->y - $b->y;
        $this->z = $a->z - $b->z;

        return $this;
    }

    public function multiply($vector)
    {
        $this->x *= $vector->x;
        $this->y *= $vector->y;
        $this->z *= $vector->z;

        return $this;
    }

    public function multiplyScalar($scalar)
    {
        $this->x *= $scalar;
        $this->y *= $scalar;
        $this->z *= $scalar;

        return $this;
    }

    public function multiplyVectors($a, $b)
    {
        $this->x = $a->x * $b->x;
        $this->y = $a->y * $b->y;
        $this->z = $a->z * $b->z;

        return $this;
    }

    public function applyEuler($euler)
    {
        return $this->applyQuaternion($this->quaternion->setFromEuler($euler));
    }

    public function applyAxisAngle($axis, $angle)
    {
        return $this->applyQuaternion($this->quaternion->setFromAxisAngle($axis, $angle));
    }

    public function applyMatrix3($m)
    {
        $x = $this->x;
        $y = $this->y;
        $z = $this->z;
        $e = $m->elements;

        $this->x = $e[0] * $x + $e[3] * $y + $e[6] * $z;
        $this->y = $e[1] * $x + $e[4] * $y + $e[7] * $z;
        $this->z = $e[2] * $x + $e[5] * $y + $e[8] * $z;

        return $this;
    }

    public function applyNormalMatrix($m)
    {
        return $this->applyMatrix3($m)->normalize();
    }

    public function applyMatrix4($m)
    {
        $x = $this->x;
        $y = $this->y;
        $z = $this->z;
        $e = $m->elements;

        $w = 1 / ($e[3] * $x + $e[7] * $y + $e[11] * $z + $e[15]);

        $this->x = ($e[0] * $x + $e[4] * $y + $e[8] * $z + $e[12]) * $w;
        $this->y = ($e[1] * $x + $e[5] * $y + $e[9] * $z + $e[13]) * $w;
        $this->z = ($e[2] * $x + $e[6] * $y + $e[10] * $z + $e[14]) * $w;

        return $this;
    }

    public function applyQuaternion($q)
    {
        // quaternion q is assumed to have unit length

        $vx = $this->x;
        $vy = $this->y;
        $vz = $this->z;

        $qx = $q->x;
        $qy = $q->y;
        $qz = $q->z;
        $qw = $q->w;

        // t = 2 * cross( q.xyz, v );
        $tx = 2 * ($qy * $vz - $qz * $vy);
        $ty = 2 * ($qz * $vx - $qx * $vz);
        $tz = 2 * ($qx * $vy - $qy * $vx);

        // v + q.w * t + cross( q.xyz, t );
        $this->x = $vx + $qw * $tx + $qy * $tz - $qz * $ty;
        $this->y = $vy + $qw * $ty + $qz * $tx - $qx * $tz;
        $this->z = $vz + $qw * $tz + $qx * $ty - $qy * $tx;

        return $this;
    }

    public function project($camera)
    {
        return $this->applyMatrix4($camera->matrixWorldInverse)->applyMatrix4($camera->projectionMatrix);
    }

    public function unproject($camera)
    {
        return $this->applyMatrix4($camera->projectionMatrixInverse)->applyMatrix4($camera->matrixWorld);
    }

    public function transformDirection($m)
    {
        // input: THREE.Matrix4 affine matrix
        // vector interpreted as a direction

        $x = $this->x;
        $y = $this->y;
        $z = $this->z;
        $e = $m->elements;

        $this->x = $e[0] * $x + $e[4] * $y + $e[8] * $z;
        $this->y = $e[1] * $x + $e[5] * $y + $e[9] * $z;
        $this->z = $e[2] * $x + $e[6] * $y + $e[10] * $z;

        return $this->normalize();
    }

    public function divide($v)
    {
        $this->x /= $v->x;
        $this->y /= $v->y;
        $this->z /= $v->z;

        return $this;
    }

    public function divideScalar($scalar)
    {
        return $this->multiplyScalar(1 / $scalar);
    }

    public function min($v)
    {
        $this->x = min($this->x, $v->x);
        $this->y = min($this->y, $v->y);
        $this->z = min($this->z, $v->z);

        return $this;
    }

    public function max($v)
    {
        $this->x = max($this->x, $v->x);
        $this->y = max($this->y, $v->y);
        $this->z = max($this->z, $v->z);

        return $this;
    }

    public function clamp($min, $max)
    {
        // assumes min < max, componentwise

        $this->x = max($min->x, min($max->x, $this->x));
        $this->y = max($min->y, min($max->y, $this->y));
        $this->z = max($min->z, min($max->z, $this->z));

        return $this;
    }

    public function clampScalar($minVal, $maxVal)
    {
        $this->x = max($minVal, min($maxVal, $this->x));
        $this->y = max($minVal, min($maxVal, $this->y));
        $this->z = max($minVal, min($maxVal, $this->z));

        return $this;
    }

    public function clampLength($min, $max)
    {
        $length = $this->length();

        if ($length === 0) {
            $length = 1;
        }

        return $this->divideScalar($length)->multiplyScalar(max($min, min($max, $length)));
    }

    public function floor()
    {
        $this->x = floor($this->x);
        $this->y = floor($this->y);
        $this->z = floor($this->z);

        return $this;
    }

    public function ceil()
    {
        $this->x = ceil($this->x);
        $this->y = ceil($this->y);
        $this->z = ceil($this->z);

        return $this;
    }

    public function round()
    {
        $this->x = round($this->x);
        $this->y = round($this->y);
        $this->z = round($this->z);

        return $this;
    }

    public function roundToZero()
    {
        $this->x = intval($this->x);
        $this->y = intval($this->y);
        $this->z = intval($this->z);

        return $this;
    }

    public function negate()
    {
        $this->x = -$this->x;
        $this->y = -$this->y;
        $this->z = -$this->z;

        return $this;
    }


    public function dot($vector)
    {
        return $this->x * $vector->x + $this->y * $vector->y + $this->z * $vector->z;
    }

    // TODO lengthSquared?

    public function lengthSq()
    {
        return $this->x * $this->x + $this->y * $this->y + $this->z * $this->z;
    }

    public function length()
    {
        return sqrt($this->lengthSq());
    }

    public function manhattanLength()
    {
        return abs($this->x) + abs($this->y) + abs($this->z);
    }

    public function normalize()
    {
        $length = $this->length();

        if ($length == 0) {
            return $this;
        }

        return $this->divideScalar($length);
    }

    public function setLength($length)
    {
        return $this->normalize()->multiplyScalar($length);
    }

    public function lerp($v, $alpha)
    {
        $this->x += ($v->x - $this->x) * $alpha;
        $this->y += ($v->y - $this->y) * $alpha;
        $this->z += ($v->z - $this->z) * $alpha;

        return $this;
    }

    public function lerpVectors($v1, $v2, $alpha)
    {
        $this->x = $v1->x + ($v2->x - $v1->x) * $alpha;
        $this->y = $v1->y + ($v2->y - $v1->y) * $alpha;
        $this->z = $v1->z + ($v2->z - $v1->z) * $alpha;

        return $this;
    }

    public function cross($v)
    {
        return $this->crossVectors($this, $v);
    }

    public function crossVectors($a, $b)
    {
        $ax = $a->x;
        $ay = $a->y;
        $az = $a->z;

        $bx = $b->x;
        $by = $b->y;
        $bz = $b->z;

        $this->x = $ay * $bz - $az * $by;
        $this->y = $az * $bx - $ax * $bz;
        $this->z = $ax * $by - $ay * $bx;

        return $this;
    }

    public function projectOnVector($v)
    {
        $denominator = $v->lengthSq();

        if ($denominator === 0) {
            return $this->set(0, 0, 0);
        }

        $scalar = $v->dot($this) / $denominator;

        return $this->copy($v)->multiplyScalar($scalar);
    }

    public function projectOnPlane($planeNormal)
    {
        $vector = $this->clone()->projectOnVector($planeNormal);

        return $this->sub($vector);
    }

    public function reflect($normal)
    {
        // reflect incident vector off plane orthogonal to normal
        // normal is assumed to have unit length

        return $this->sub($normal->clone()->multiplyScalar(2 * $this->dot($normal)));
    }

    public function angleTo($v)
    {
        $denominator = sqrt($this->lengthSq() * $v->lengthSq());

        if ($denominator === 0) {
            return pi() / 2;
        }

        $theta = $this->dot($v) / $denominator;

        // clamp, to handle numerical problems

        return acos(MathUtils::clamp($theta, -1, 1));
    }

    public function distanceTo($v)
    {
        return sqrt($this->distanceToSquared($v));
    }

    public function distanceToSquared($v)
    {
        $dx = $this->x - $v->x;
        $dy = $this->y - $v->y;
        $dz = $this->z - $v->z;

        return $dx * $dx + $dy * $dy + $dz * $dz;
    }

    public function manhattanDistanceTo($v)
    {
        return abs($this->x - $v->x) + abs($this->y - $v->y) + abs($this->z - $v->z);
    }

    public function setFromSpherical($s)
    {
        return $this->setFromSphericalCoords($s->radius, $s->phi, $s->theta);
    }

    public function setFromSphericalCoords($radius, $phi, $theta)
    {
        $sinPhiRadius = sin($phi) * $radius;

        $this->x = $sinPhiRadius * sin($theta);
        $this->y = cos($phi) * $radius;
        $this->z = $sinPhiRadius * cos($theta);

        return $this;
    }

    public function setFromCylindrical($c)
    {
        return $this->setFromCylindricalCoords($c->radius, $c->theta, $c->y);
    }

    public function setFromCylindricalCoords($radius, $theta, $y)
    {
        $this->x = $radius * sin($theta);
        $this->y = $y;
        $this->z = $radius * cos($theta);

        return $this;
    }

    public function setFromMatrixPosition($m)
    {
        $e = $m->elements;

        $this->x = $e[12];
        $this->y = $e[13];
        $this->z = $e[14];

        return $this;
    }

    public function setFromMatrixScale($m)
    {
        $sx = $this->setFromMatrixColumn($m, 0)->length();
        $sy = $this->setFromMatrixColumn($m, 1)->length();
        $sz = $this->setFromMatrixColumn($m, 2)->length();

        $this->x = $sx;
        $this->y = $sy;
        $this->z = $sz;

        return $this;
    }

    public function setFromMatrixColumn($m, $index)
    {
        return $this->fromArray($m->elements, $index * 4);
    }

    public function setFromMatrix3Column($m, $index)
    {
        return $this->fromArray($m->elements, $index * 3);
    }

    public function setFromEuler($e)
    {
        $this->x = $e->_x;
        $this->y = $e->_y;
        $this->z = $e->_z;

        return $this;
    }

    public function setFromColor($c)
    {
        $this->x = $c->r;
        $this->y = $c->g;
        $this->z = $c->b;

        return $this;
    }

    public function equals($vector)
    {
        return ($vector->x == $this->x) && ($vector->y == $this->y) && ($vector->z == $this->z);
    }

    public function fromArray($array, $offset = 0)
    {
        $this->x = $array[$offset + 0];
        $this->y = $array[$offset + 1];
        $this->z = $array[$offset + 2];

        return $this;
    }

    public function toArray(&$array = [], $offset = 0)
    {
        $array[$offset + 0] = $this->x;
        $array[$offset + 1] = $this->y;
        $array[$offset + 2] = $this->z;

        return $array;
    }

    public function fromBufferAttribute($attribute, $index)
    {
        $this->x = $attribute->getX($index);
        $this->y = $attribute->getY($index);
        $this->z = $attribute->getZ($index);

        return $this;
    }

    public function random()
    {
        $this->x = MathUtils::rand();
        $this->y = MathUtils::rand();
        $this->z = MathUtils::rand();

        return $this;
    }

    public function randomDirection()
    {
        // Derived from https://mathworld.wolfram.com/SpherePointPicking.html

        $u = (MathUtils::rand() - 0.5) * 2;
        $t = MathUtils::rand() * pi() * 2;
        $f = sqrt(1 - $u ** 2);

        $this->x = $f * cos($t);
        $this->y = $f * sin($t);
        $this->z = $u;

        return $this;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        if ($this->position === 0) {
            return $this->x;
        } elseif ($this->position === 1) {
            return $this->y;
        } elseif ($this->position === 2) {
            return $this->z;
        }
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function valid(): bool
    {
        return $this->position < 3;
    }

    public static function xAxis()
    {
        return new Vector3(1, 0, 0);
    }

    public static function yAxis()
    {
        return new Vector3(0, 1, 0);
    }

    public static function zAxis()
    {
        return new Vector3(0, 0, 1);
    }
}

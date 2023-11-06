<?php

namespace ThreePHP\Math;

use ThreePHP\Core\ChangeHandler;

class Quaternion implements \Iterator
{
    use ChangeHandler;

    private float $x;
    private float $y;
    private float $z;
    private float $w;

    private $position = 0;

    public function __construct($x = 0, $y = 0, $z = 0, $w = 1)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->w = $w;
    }

    public static function slerpFlat(&$dst, $dstOffset, &$src0, $srcOffset0, &$src1, $srcOffset1, $t)
    {
        $x0 = $src0[$srcOffset0 + 0];
        $y0 = $src0[$srcOffset0 + 1];
        $z0 = $src0[$srcOffset0 + 2];
        $w0 = $src0[$srcOffset0 + 3];

        $x1 = $src1[$srcOffset1 + 0];
        $y1 = $src1[$srcOffset1 + 1];
        $z1 = $src1[$srcOffset1 + 2];
        $w1 = $src1[$srcOffset1 + 3];

        if ($t === 0) {
            $dst[$dstOffset + 0] = $x0;
            $dst[$dstOffset + 1] = $y0;
            $dst[$dstOffset + 2] = $z0;
            $dst[$dstOffset + 3] = $w0;
            return;
        }

        if ($t === 1) {
            $dst[$dstOffset + 0] = $x1;
            $dst[$dstOffset + 1] = $y1;
            $dst[$dstOffset + 2] = $z1;
            $dst[$dstOffset + 3] = $w1;
            return;
        }

        if ($w0 !== $w1 || $x0 !== $x1 || $y0 !== $y1 || $z0 !== $z1) {
            $s = 1 - $t;
            $cos = $x0 * $x1 + $y0 * $y1 + $z0 * $z1 + $w0 * $w1;
            $dir = ($cos >= 0) ? 1 : -1;
            $sqrSin = 1 - $cos * $cos;

            if ($sqrSin > PHP_FLOAT_EPSILON) {
                $sin = sqrt($sqrSin);
                $len = atan2($sin, $cos * $dir);
                $s = sin($s * $len) / $sin;
                $t = sin($t * $len) / $sin;
            }

            $tDir = $t * $dir;

            $x0 = $x0 * $s + $x1 * $tDir;
            $y0 = $y0 * $s + $y1 * $tDir;
            $z0 = $z0 * $s + $z1 * $tDir;
            $w0 = $w0 * $s + $w1 * $tDir;

            if ($s === 1 - $t) {
                $f = 1 / sqrt($x0 * $x0 + $y0 * $y0 + $z0 * $z0 + $w0 * $w0);
                $x0 *= $f;
                $y0 *= $f;
                $z0 *= $f;
                $w0 *= $f;
            }
        }

        $dst[$dstOffset + 0] = $x0;
        $dst[$dstOffset + 1] = $y0;
        $dst[$dstOffset + 2] = $z0;
        $dst[$dstOffset + 3] = $w0;
    }

    public static function multiplyQuaternionsFlat(&$dst, $dstOffset, &$src0, $srcOffset0, &$src1, $srcOffset1)
    {
        $x0 = $src0[$srcOffset0 + 0];
        $y0 = $src0[$srcOffset0 + 1];
        $z0 = $src0[$srcOffset0 + 2];
        $w0 = $src0[$srcOffset0 + 3];

        $x1 = $src1[$srcOffset1 + 0];
        $y1 = $src1[$srcOffset1 + 1];
        $z1 = $src1[$srcOffset1 + 2];
        $w1 = $src1[$srcOffset1 + 3];

        $dst[$dstOffset + 0] = $x0 * $w1 + $w0 * $x1 + $y0 * $z1 - $z0 * $y1;
        $dst[$dstOffset + 1] = $y0 * $w1 + $w0 * $y1 + $z0 * $x1 - $x0 * $z1;
        $dst[$dstOffset + 2] = $z0 * $w1 + $w0 * $z1 + $x0 * $y1 - $y0 * $x1;
        $dst[$dstOffset + 3] = $w0 * $w1 - $x0 * $x1 - $y0 * $y1 - $z0 * $z1;

        return $dst;
    }

    public function set($x, $y, $z, $w): Quaternion
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->w = $w;

        $this->onChangeCallback();

        return $this;
    }

    public function clone(): Quaternion
    {
        return new static($this->x, $this->y, $this->z, $this->w);
    }

    public function copy(Quaternion $quaternion): Quaternion
    {
        $this->x = $quaternion->x;
        $this->y = $quaternion->y;
        $this->z = $quaternion->z;
        $this->w = $quaternion->w;

        $this->onChangeCallback();

        return $this;
    }

    public function setFromEuler(Euler $euler, $update = true): Quaternion
    {
        $x = $euler->x;
        $y = $euler->y;
        $z = $euler->z;
        $order = $euler->order;

        $c1 = cos($x / 2);
        $c2 = cos($y / 2);
        $c3 = cos($z / 2);

        $s1 = sin($x / 2);
        $s2 = sin($y / 2);
        $s3 = sin($z / 2);

        switch ($order) {
            case 'XYZ':
                $this->x = $s1 * $c2 * $c3 + $c1 * $s2 * $s3;
                $this->y = $c1 * $s2 * $c3 - $s1 * $c2 * $s3;
                $this->z = $c1 * $c2 * $s3 + $s1 * $s2 * $c3;
                $this->w = $c1 * $c2 * $c3 - $s1 * $s2 * $s3;
                break;
            case 'YXZ':
                $this->x = $s1 * $c2 * $c3 + $c1 * $s2 * $s3;
                $this->y = $c1 * $s2 * $c3 - $s1 * $c2 * $s3;
                $this->z = $c1 * $c2 * $s3 - $s1 * $s2 * $c3;
                $this->w = $c1 * $c2 * $c3 + $s1 * $s2 * $s3;
                break;
            case 'ZXY':
                $this->x = $s1 * $c2 * $c3 - $c1 * $s2 * $s3;
                $this->y = $c1 * $s2 * $c3 + $s1 * $c2 * $s3;
                $this->z = $c1 * $c2 * $s3 + $s1 * $s2 * $c3;
                $this->w = $c1 * $c2 * $c3 - $s1 * $s2 * $s3;
                break;
            case 'ZYX':
                $this->x = $s1 * $c2 * $c3 - $c1 * $s2 * $s3;
                $this->y = $c1 * $s2 * $c3 + $s1 * $c2 * $s3;
                $this->z = $c1 * $c2 * $s3 - $s1 * $s2 * $c3;
                $this->w = $c1 * $c2 * $c3 + $s1 * $s2 * $s3;
                break;
            case 'YZX':
                $this->x = $s1 * $c2 * $c3 + $c1 * $s2 * $s3;
                $this->y = $c1 * $s2 * $c3 + $s1 * $c2 * $s3;
                $this->z = $c1 * $c2 * $s3 - $s1 * $s2 * $c3;
                $this->w = $c1 * $c2 * $c3 - $s1 * $s2 * $s3;
                break;
            case 'XZY':
                $this->x = $s1 * $c2 * $c3 - $c1 * $s2 * $s3;
                $this->y = $c1 * $s2 * $c3 - $s1 * $c2 * $s3;
                $this->z = $c1 * $c2 * $s3 + $s1 * $s2 * $c3;
                $this->w = $c1 * $c2 * $c3 + $s1 * $s2 * $s3;
                break;
            default:
                if ($update !== false) {
                    $this->onChangeCallback();
                }
                return $this;
        }

        if ($update !== false) {
            $this->onChangeCallback();
        }

        return $this;
    }

    public function setFromAxisAngle($axis, $angle): Quaternion
    {
        // http://www.euclideanspace.com/maths/geometry/rotations/conversions/angleToQuaternion/index.htm
        // assumes axis is normalized

        $halfAngle = $angle / 2;
        $s = sin($halfAngle);

        $this->x = $axis->x * $s;
        $this->y = $axis->y * $s;
        $this->z = $axis->z * $s;
        $this->w = cos($halfAngle);

        $this->onChangeCallback();

        return $this;
    }

    public function setFromRotationMatrix($m): Quaternion
    {
        // http://www.euclideanspace.com/maths/geometry/rotations/conversions/matrixToQuaternion/index.htm
        // assumes the upper 3x3 of m is a pure rotation matrix (i.e, unscaled)

        $te = $m->elements;

        $m11 = $te[0];
        $m12 = $te[4];
        $m13 = $te[8];
        $m21 = $te[1];
        $m22 = $te[5];
        $m23 = $te[9];
        $m31 = $te[2];
        $m32 = $te[6];
        $m33 = $te[10];

        $trace = $m11 + $m22 + $m33;

        if ($trace > 0) {
            $s = 0.5 / sqrt($trace + 1.0);
            $this->w = 0.25 / $s;
            $this->x = ($m32 - $m23) * $s;
            $this->y = ($m13 - $m31) * $s;
            $this->z = ($m21 - $m12) * $s;
        } elseif ($m11 > $m22 && $m11 > $m33) {
            $s = 2.0 * sqrt(1.0 + $m11 - $m22 - $m33);
            $this->w = ($m32 - $m23) / $s;
            $this->x = 0.25 * $s;
            $this->y = ($m12 + $m21) / $s;
            $this->z = ($m13 + $m31) / $s;
        } elseif ($m22 > $m33) {
            $s = 2.0 * sqrt(1.0 + $m22 - $m11 - $m33);
            $this->w = ($m13 - $m31) / $s;
            $this->x = ($m12 + $m21) / $s;
            $this->y = 0.25 * $s;
            $this->z = ($m23 + $m32) / $s;
        } else {
            $s = 2.0 * sqrt(1.0 + $m33 - $m11 - $m22);
            $this->w = ($m21 - $m12) / $s;
            $this->x = ($m13 + $m31) / $s;
            $this->y = ($m23 + $m32) / $s;
            $this->z = 0.25 * $s;
        }

        $this->onChangeCallback();

        return $this;
    }

    public function setFromUnitVectors($vFrom, $vTo): Quaternion
    {
        // assumes direction vectors vFrom and vTo are normalized

        $r = $vFrom->dot($vTo) + 1;

        if ($r < PHP_FLOAT_EPSILON) {
            // vFrom and vTo point in opposite directions
            $r = 0;

            if (abs($vFrom->x) > abs($vFrom->z)) {
                $this->x = -$vFrom->y;
                $this->y = $vFrom->x;
                $this->z = 0;
                $this->w = $r;
            } else {
                $this->x = 0;
                $this->y = -$vFrom->z;
                $this->z = $vFrom->y;
                $this->w = $r;
            }
        } else {
            $this->x = $vFrom->y * $vTo->z - $vFrom->z * $vTo->y;
            $this->y = $vFrom->z * $vTo->x - $vFrom->x * $vTo->z;
            $this->z = $vFrom->x * $vTo->y - $vFrom->y * $vTo->x;
            $this->w = $r;
        }

        return $this->normalize();
    }

    public function angleTo($q)
    {
        return 2 * acos(abs(MathUtils::clamp($this->dot($q), -1, 1)));
    }

    public function rotateTowards($q, $step)
    {
        $angle = $this->angleTo($q);

        if ($angle === 0) {
            return $this;
        }

        $t = min(1, $step / $angle);
        $this->slerp($q, $t);

        return $this;
    }

    public function identity()
    {
        return $this->set(0, 0, 0, 1);
    }

    public function invert()
    {
        // quaternion is assumed to have unit length
        return $this->conjugate();
    }

    public function conjugate()
    {
        $this->x *= -1;
        $this->y *= -1;
        $this->z *= -1;
        $this->onChangeCallback();

        return $this;
    }

    public function dot($v)
    {
        return $this->x * $v->x + $this->y * $v->y + $this->z * $v->z + $this->w * $v->w;
    }

    public function lengthSq()
    {
        return $this->x * $this->x + $this->y * $this->y + $this->z * $this->z + $this->w * $this->w;
    }

    public function length()
    {
        return sqrt($this->x * $this->x + $this->y * $this->y + $this->z * $this->z + $this->w * $this->w);
    }

    public function normalize()
    {
        $l = $this->length();

        if ($l === 0) {
            $this->x = 0;
            $this->y = 0;
            $this->z = 0;
            $this->w = 1;
        } else {
            $l = 1 / $l;
            $this->x = $this->x * $l;
            $this->y = $this->y * $l;
            $this->z = $this->z * $l;
            $this->w = $this->w * $l;
        }

        $this->onChangeCallback();

        return $this;
    }

    public function multiply($q)
    {
        return $this->multiplyQuaternions($this, $q);
    }

    public function premultiply($q)
    {
        return $this->multiplyQuaternions($q, $this);
    }

    public function multiplyQuaternions($a, $b)
    {
        // from http://www.euclideanspace.com/maths/algebra/realNormedAlgebra/quaternions/code/index.htm

        $qax = $a->x;
        $qay = $a->y;
        $qaz = $a->z;
        $qaw = $a->w;

        $qbx = $b->x;
        $qby = $b->y;
        $qbz = $b->z;
        $qbw = $b->w;

        $this->x = $qax * $qbw + $qaw * $qbx + $qay * $qbz - $qaz * $qby;
        $this->y = $qay * $qbw + $qaw * $qby + $qaz * $qbx - $qax * $qbz;
        $this->z = $qaz * $qbw + $qaw * $qbz + $qax * $qby - $qay * $qbx;
        $this->w = $qaw * $qbw - $qax * $qbx - $qay * $qby - $qaz * $qbz;

        $this->onChangeCallback();

        return $this;
    }

    public function slerp($qb, $t)
    {
        if ($t === 0) {
            return $this;
        }
        if ($t === 1) {
            return $this->copy($qb);
        }

        $x = $this->x;
        $y = $this->y;
        $z = $this->z;
        $w = $this->w;

        // http://www.euclideanspace.com/maths/algebra/realNormedAlgebra/quaternions/slerp/

        $cosHalfTheta = $w * $qb->w + $x * $qb->x + $y * $qb->y + $z * $qb->z;

        if ($cosHalfTheta < 0) {
            $this->w = -$qb->w;
            $this->x = -$qb->x;
            $this->y = -$qb->y;
            $this->z = -$qb->z;
            $cosHalfTheta = -$cosHalfTheta;
        } else {
            $this->copy($qb);
        }

        if ($cosHalfTheta >= 1.0) {
            $this->w = $w;
            $this->x = $x;
            $this->y = $y;
            $this->z = $z;
            return $this;
        }

        $sqrSinHalfTheta = 1.0 - $cosHalfTheta * $cosHalfTheta;

        if ($sqrSinHalfTheta <= PHP_FLOAT_EPSILON) {
            $s = 1 - $t;
            $this->w = $s * $w + $t * $this->w;
            $this->x = $s * $x + $t * $this->x;
            $this->y = $s * $y + $t * $this->y;
            $this->z = $s * $z + $t * $this->z;
            $this->normalize();
            $this->onChangeCallback();
            return $this;
        }

        $sinHalfTheta = sqrt($sqrSinHalfTheta);
        $halfTheta = atan2($sinHalfTheta, $cosHalfTheta);
        $ratioA = sin((1 - $t) * $halfTheta) / $sinHalfTheta;
        $ratioB = sin($t * $halfTheta) / $sinHalfTheta;

        $this->w = ($w * $ratioA + $this->w * $ratioB);
        $this->x = ($x * $ratioA + $this->x * $ratioB);
        $this->y = ($y * $ratioA + $this->y * $ratioB);
        $this->z = ($z * $ratioA + $this->z * $ratioB);

        $this->onChangeCallback();

        return $this;
    }

    public function slerpQuaternions($qa, $qb, $t)
    {
        return $this->copy($qa)->slerp($qb, $t);
    }

    public function random()
    {
        // Derivato da http://planning.cs.uiuc.edu/node198.html
        // Nota, questa fonte utilizza l'ordinamento w, x, y, z,
        // quindi invertiamo l'ordine qui sotto.

        $u1 = MathUtils::rand();
        $sqrt1u1 = sqrt(1 - $u1);
        $sqrtu1 = sqrt($u1);

        $u2 = 2 * M_PI * MathUtils::rand();
        $u3 = 2 * M_PI * MathUtils::rand();

        return $this->set(
            $sqrt1u1 * cos($u2),
            $sqrtu1 * sin($u3),
            $sqrtu1 * cos($u3),
            $sqrt1u1 * sin($u2)
        );
    }

    public function equals($quaternion)
    {
        return ($quaternion->x == $this->x) && ($quaternion->y == $this->y) && ($quaternion->z == $this->z) && ($quaternion->w == $this->w);
    }

    public function fromArray($array, $offset = 0)
    {
        $this->x = $array[$offset];
        $this->y = $array[$offset + 1];
        $this->z = $array[$offset + 2];
        $this->w = $array[$offset + 3];

        $this->onChangeCallback();

        return $this;
    }

    public function toArray($array = [], $offset = 0)
    {
        $array[$offset] = $this->x;
        $array[$offset + 1] = $this->y;
        $array[$offset + 2] = $this->z;
        $array[$offset + 3] = $this->w;

        return $array;
    }

    public function fromBufferAttribute($attribute, $index)
    {
        $this->x = $attribute->getX($index);
        $this->y = $attribute->getY($index);
        $this->z = $attribute->getZ($index);
        $this->w = $attribute->getW($index);

        return $this;
    }

    public function toJSON()
    {
        return $this->toArray();
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
        } elseif ($this->position === 3) {
            return $this->w;
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
        return $this->position < 4;
    }
}

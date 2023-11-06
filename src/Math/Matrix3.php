<?php

namespace ThreePHP\Math;

class Matrix3
{
    public $elements;

    function __construct($n11 = null, $n12 = null, $n13 = null, $n21 = null, $n22 = null, $n23 = null, $n31 = null, $n32 = null, $n33 = null)
    {
        $this->elements = [
            1, 0, 0,
            0, 1, 0,
            0, 0, 1,
        ];

        if (!is_null($n11)) {
            $this->set($n11, $n12, $n13, $n21, $n22, $n23,  $n31, $n32, $n33);
        }
    }

    function set($n11, $n12, $n13, $n21, $n22, $n23, $n31, $n32, $n33): static
    {
        $te = &$this->elements;

        $te[0] = $n11;
        $te[3] = $n12;
        $te[6] = $n13;
        $te[1] = $n21;
        $te[4] = $n22;
        $te[7] = $n23;
        $te[2] = $n31;
        $te[5] = $n32;
        $te[8] = $n33;

        return $this;
    }

    function identity(): static
    {
        $this->set(
            1,
            0,
            0,
            0,
            1,
            0,
            0,
            0,
            1,
        );

        return $this;
    }

    function copy(Matrix3 $m): static
    {
        $te = &$this->elements;
        $me = $m->elements;

        $te[0] = $me[0];
        $te[1] = $me[1];
        $te[2] = $me[2];
        $te[3] = $me[3];
        $te[4] = $me[4];
        $te[5] = $me[5];
        $te[6] = $me[6];
        $te[7] = $me[7];
        $te[8] = $me[8];

        return $this;
    }

    function extractBasis(Vector3 $xAxis, Vector3 $yAxis, Vector3 $zAxis): static
    {

        $xAxis->setFromMatrix3Column($this, 0);
        $yAxis->setFromMatrix3Column($this, 1);
        $zAxis->setFromMatrix3Column($this, 2);

        return $this;
    }

    public function setFromMatrix4(Matrix4 $matrix): static
    {
        $me = $matrix->elements;

        $this->set(
            $me[0],
            $me[4],
            $me[8],
            $me[1],
            $me[5],
            $me[9],
            $me[2],
            $me[6],
            $me[10]
        );

        return $this;
    }

    public function multiply(MAtrix3 $matrix): static
    {
        return $this->multiplyMatrices($this, $matrix);
    }

    public function premultiply(Matrix3 $m): static
    {
        return $this->multiplyMatrices($m, $this);
    }

    public function multiplyMatrices(Matrix3 $a, Matrix3 $b): static
    {
        $ae = $a->elements;
        $be = $b->elements;
        $te = $this->elements;

        $a11 = $ae[0];
        $a12 = $ae[3];
        $a13 = $ae[6];
        $a21 = $ae[1];
        $a22 = $ae[4];
        $a23 = $ae[7];
        $a31 = $ae[2];
        $a32 = $ae[5];
        $a33 = $ae[8];

        $b11 = $be[0];
        $b12 = $be[3];
        $b13 = $be[6];
        $b21 = $be[1];
        $b22 = $be[4];
        $b23 = $be[7];
        $b31 = $be[2];
        $b32 = $be[5];
        $b33 = $be[8];

        $te[0] = $a11 * $b11 + $a12 * $b21 + $a13 * $b31;
        $te[3] = $a11 * $b12 + $a12 * $b22 + $a13 * $b32;
        $te[6] = $a11 * $b13 + $a12 * $b23 + $a13 * $b33;

        $te[1] = $a21 * $b11 + $a22 * $b21 + $a23 * $b31;
        $te[4] = $a21 * $b12 + $a22 * $b22 + $a23 * $b32;
        $te[7] = $a21 * $b13 + $a22 * $b23 + $a23 * $b33;

        $te[2] = $a31 * $b11 + $a32 * $b21 + $a33 * $b31;
        $te[5] = $a31 * $b12 + $a32 * $b22 + $a33 * $b32;
        $te[8] = $a31 * $b13 + $a32 * $b23 + $a33 * $b33;

        return $this;
    }

    public function multiplyScalar($s): static
    {
        $te = $this->elements;

        $te[0] *= $s;
        $te[3] *= $s;
        $te[6] *= $s;
        $te[1] *= $s;
        $te[4] *= $s;
        $te[7] *= $s;
        $te[2] *= $s;
        $te[5] *= $s;
        $te[8] *= $s;

        return $this;
    }

    public function determinant(): float
    {
        $te = $this->elements;

        $a = $te[0];
        $b = $te[1];
        $c = $te[2];
        $d = $te[3];
        $e = $te[4];
        $f = $te[5];
        $g = $te[6];
        $h = $te[7];
        $i = $te[8];

        return $a * $e * $i - $a * $f * $h - $b * $d * $i + $b * $f * $g + $c * $d * $h - $c * $e * $g;
    }

    public function invert(): static
    {
        $te = $this->elements;

        $n11 = $te[0];
        $n21 = $te[1];
        $n31 = $te[2];
        $n12 = $te[3];
        $n22 = $te[4];
        $n32 = $te[5];
        $n13 = $te[6];
        $n23 = $te[7];
        $n33 = $te[8];

        $t11 = $n33 * $n22 - $n32 * $n23;
        $t12 = $n32 * $n13 - $n33 * $n12;
        $t13 = $n23 * $n12 - $n22 * $n13;

        $det = $n11 * $t11 + $n21 * $t12 + $n31 * $t13;

        if ($det === 0) {
            return $this->set(0, 0, 0, 0, 0, 0, 0, 0, 0);
        }

        $detInv = 1 / $det;

        $te[0] = $t11 * $detInv;
        $te[1] = ($n31 * $n23 - $n33 * $n21) * $detInv;
        $te[2] = ($n32 * $n21 - $n31 * $n22) * $detInv;

        $te[3] = $t12 * $detInv;
        $te[4] = ($n33 * $n11 - $n31 * $n13) * $detInv;
        $te[5] = ($n31 * $n12 - $n32 * $n11) * $detInv;

        $te[6] = $t13 * $detInv;
        $te[7] = ($n21 * $n13 - $n23 * $n11) * $detInv;
        $te[8] = ($n22 * $n11 - $n21 * $n12) * $detInv;

        return $this;
    }

    public function transpose(): static
    {
        $m = $this->elements;

        $tmp = $m[1];
        $m[1] = $m[3];
        $m[3] = $tmp;
        $tmp = $m[2];
        $m[2] = $m[6];
        $m[6] = $tmp;
        $tmp = $m[5];
        $m[5] = $m[7];
        $m[7] = $tmp;

        return $this;
    }

    public function getNormalMatrix(Matrix4 $matrix): static
    {
        $this->setFromMatrix4($matrix)->invert()->transpose();
        return $this;
    }

    public function transposeIntoArray(array &$r): static
    {
        $m = $this->elements;

        $r[0] = $m[0];
        $r[1] = $m[3];
        $r[2] = $m[6];
        $r[3] = $m[1];
        $r[4] = $m[4];
        $r[5] = $m[7];
        $r[6] = $m[2];
        $r[7] = $m[5];
        $r[8] = $m[8];

        return $this;
    }

    public function setUvTransform($tx, $ty, $sx, $sy, $rotation, $cx, $cy): static
    {
        $c = cos($rotation);
        $s = sin($rotation);

        $this->set(
            $sx * $c,
            $sx * $s,
            - ($sx * ($c * $cx + $s * $cy)) + $cx + $tx,
            - ($sy * $s),
            $sy * $c,
            - ($sy * (-$s * $cx + $c * $cy)) + $cy + $ty,
            0,
            0,
            1
        );

        return $this;
    }

    public function scale($sx, $sy): static
    {
        $scaleMatrix = new static();
        $scaleMatrix->makeScale($sx, $sy);

        $this->premultiply($scaleMatrix);

        return $this;
    }

    public function rotate($theta): static
    {
        $rotationMatrix = new Matrix3();
        $rotationMatrix->makeRotation(-$theta);

        $this->premultiply($rotationMatrix);

        return $this;
    }

    public function translate($tx, $ty): static
    {
        $translationMatrix = new Matrix3();
        $translationMatrix->makeTranslation($tx, $ty);

        $this->premultiply($translationMatrix);

        return $this;
    }

    public function makeTranslation($x, $y): static
    {
        if ($x instanceof Vector2) {
            $this->set(
                1,
                0,
                $x->x,
                0,
                1,
                $x->y,
                0,
                0,
                1
            );
        } else {
            $this->set(
                1,
                0,
                $x,
                0,
                1,
                $y,
                0,
                0,
                1
            );
        }

        return $this;
    }

    public function makeRotation($theta): static
    {
        // counterclockwise
        $c = cos($theta);
        $s = sin($theta);

        $this->set(
            $c,
            -$s,
            0,
            $s,
            $c,
            0,
            0,
            0,
            1
        );

        return $this;
    }

    public function makeScale($x, $y): static
    {
        $this->set(
            $x,
            0,
            0,
            0,
            $y,
            0,
            0,
            0,
            1
        );

        return $this;
    }

    public function equals(Matrix3 $matrix): bool
    {
        $te = $this->elements;
        $me = $matrix->elements;

        for ($i = 0; $i < 9; $i++) {
            if ($te[$i] != $me[$i]) {
                return false;
            }
        }

        return true;
    }

    public function fromArray($array, $offset = 0): static
    {
        for ($i = 0; $i < 9; $i++) {
            $this->elements[$i] = $array[$i + $offset];
        }
        return $this;
    }

    public function toArray(array $array = [], $offset = 0): array
    {
        $te = $this->elements;

        $array[$offset] = $te[0];
        $array[$offset + 1] = $te[1];
        $array[$offset + 2] = $te[2];

        $array[$offset + 3] = $te[3];
        $array[$offset + 4] = $te[4];
        $array[$offset + 5] = $te[5];

        $array[$offset + 6] = $te[6];
        $array[$offset + 7] = $te[7];
        $array[$offset + 8] = $te[8];

        return $array;
    }

    public function clone()
    {
        return (new static())->fromArray($this->elements);
    }
}

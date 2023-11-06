<?php

namespace ThreePHP\Math;

use ThreePHP\Core\ChangeHandler;
use ThreePHP\Math\MathUtils;

class Euler implements \Iterator
{
    use ChangeHandler;

    private float $x;
    private float $y;
    private float $z;
    private string $order;
    private Matrix4 $matrix;

    private $position = 0;

    const DEFAULT_ORDER = 'XYZ';

    public function __construct($x = 0, $y = 0, $z = 0, $order = self::DEFAULT_ORDER)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->order = $order;

        $this->matrix = new Matrix4();
    }

    public function set($x, $y, $z, $order = null): Euler
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;

        if ($order !== null) {
            $this->order = $order;
        }

        $this->onChangeCallback();
        return $this;
    }

    public function clone(): Euler
    {
        return new static($this->x, $this->y, $this->z, $this->order);
    }

    public function copy(Euler $euler): Euler
    {
        $this->x = $euler->x;
        $this->y = $euler->y;
        $this->z = $euler->z;
        $this->order = $euler->order;

        $this->onChangeCallback();
        return $this;
    }

    public function setFromRotationMatrix($matrix, $order = null, $update = true): Euler
    {
        // Supponiamo che la parte superiore 3x3 di $matrix sia una matrice di pura rotazione (cioè, non scalata).
        $te = $matrix->elements;
        $m11 = $te[0];
        $m12 = $te[4];
        $m13 = $te[8];
        $m21 = $te[1];
        $m22 = $te[5];
        $m23 = $te[9];
        $m31 = $te[2];
        $m32 = $te[6];
        $m33 = $te[10];

        if ($order === null) {
            $order = $this->order;
        }

        switch ($order) {
            case 'XYZ':
                $this->y = asin(MathUtils::clamp($m13, -1, 1));
                if (abs($m13) < 0.9999999) {
                    $this->x = atan2(-$m23, $m33);
                    $this->z = atan2(-$m12, $m11);
                } else {
                    $this->x = atan2($m32, $m22);
                    $this->z = 0;
                }
                break;

            case 'YXZ':
                $this->x = asin(-MathUtils::clamp($m23, -1, 1));
                if (abs($m23) < 0.9999999) {
                    $this->y = atan2($m13, $m33);
                    $this->z = atan2($m21, $m22);
                } else {
                    $this->y = atan2(-$m31, $m11);
                    $this->z = 0;
                }
                break;

            case 'ZXY':
                $this->x = asin(MathUtils::clamp($m32, -1, 1));
                if (abs($m32) < 0.9999999) {
                    $this->y = atan2(-$m31, $m33);
                    $this->z = atan2(-$m12, $m22);
                } else {
                    $this->y = 0;
                    $this->z = atan2($m21, $m11);
                }
                break;

            case 'ZYX':
                $this->y = asin(-MathUtils::clamp($m31, -1, 1));
                if (abs($m31) < 0.9999999) {
                    $this->x = atan2($m32, $m33);
                    $this->z = atan2($m21, $m11);
                } else {
                    $this->x = 0;
                    $this->z = atan2(-$m12, $m22);
                }
                break;

            case 'YZX':
                $this->z = asin(MathUtils::clamp($m21, -1, 1));
                if (abs($m21) < 0.9999999) {
                    $this->x = atan2(-$m23, $m22);
                    $this->y = atan2(-$m31, $m11);
                } else {
                    $this->x = 0;
                    $this->y = atan2($m13, $m33);
                }
                break;

            case 'XZY':
                $this->z = asin(-MathUtils::clamp($m12, -1, 1));
                if (abs($m12) < 0.9999999) {
                    $this->x = atan2($m32, $m22);
                    $this->y = atan2($m13, $m11);
                } else {
                    $this->x = atan2(-$m23, $m33);
                    $this->y = 0;
                }
                break;

            default:
                throw new \Exception('unknown order: ' . $order);
                break;
        }

        $this->order = $order;

        if ($update) {
            $this->onChangeCallback();
        }

        return $this;
    }

    public function setFromQuaternion(Quaternion $quaternion, $order = null, $update = true)
    {
        $this->matrix->makeRotationFromQuaternion($quaternion);
        return $this->setFromRotationMatrix($this->matrix, $order, $update);
    }

    public function setFromVector3(Vector3 $vector, $order = null)
    {
        $this->set($vector->x, $vector->y, $vector->z, $order);
        return $this;
    }

    public function reorder($newOrder)
    {
        $quaternion = new Quaternion();
        return $this->setFromQuaternion($quaternion->setFromEuler($this), $newOrder, true);
    }

    public function equals($euler)
    {
        return ($euler->x() == $this->x) && ($euler->y() == $this->y) && ($euler->z() == $this->z) && ($euler->order == $this->order);
    }

    public function fromArray($array)
    {
        $this->x = $array[0];
        $this->y = $array[1];
        $this->z = $array[2];
        if (isset($array[3])) {
            $this->order = $array[3];
        }

        $this->onChangeCallback();
        return $this;
    }

    public function toArray($array = [], $offset = 0)
    {
        $array[$offset + 0] = $this->x;
        $array[$offset + 1] = $this->y;
        $array[$offset + 2] = $this->z;
        $array[$offset + 3] = $this->order;

        return $array;
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
            return $this->order;
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

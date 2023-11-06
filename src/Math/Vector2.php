<?php

namespace ThreePHP\Math;

class Vector2 implements \Iterator
{
    public $x;
    public $y;

    private $position = 0;


    public function __construct($x = 0, $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getWidth()
    {
        return $this->x;
    }

    public function setWidth($value)
    {
        $this->x = $value;
    }

    public function getHeight()
    {
        return $this->y;
    }

    public function setHeight($value)
    {
        $this->y = $value;
    }

    public function set($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
        return $this;
    }

    public function setScalar($scalar)
    {
        $this->x = $scalar;
        $this->y = $scalar;
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

    public function setComponent($index, $value)
    {
        switch ($index) {
            case 0:
                $this->x = $value;
                break;
            case 1:
                $this->y = $value;
                break;
            default:
                throw new \Exception('index is out of range: ' . $index);
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
            default:
                throw new \Exception('index is out of range: ' . $index);
        }
    }

    public function clone()
    {
        return new self($this->x, $this->y);
    }

    public function copy($v)
    {
        $this->x = $v->x;
        $this->y = $v->y;
        return $this;
    }

    public function add($v)
    {
        $this->x += $v->x;
        $this->y += $v->y;
        return $this;
    }

    public function addScalar($s)
    {
        $this->x += $s;
        $this->y += $s;
        return $this;
    }

    public function addVectors($a, $b)
    {
        $this->x = $a->x + $b->x;
        $this->y = $a->y + $b->y;
        return $this;
    }

    public function addScaledVector($v, $s)
    {
        $this->x += $v->x * $s;
        $this->y += $v->y * $s;
        return $this;
    }

    public function sub($v)
    {
        $this->x -= $v->x;
        $this->y -= $v->y;
        return $this;
    }

    public function subScalar($s)
    {
        $this->x -= $s;
        $this->y -= $s;
        return $this;
    }

    public function subVectors($a, $b)
    {
        $this->x = $a->x - $b->x;
        $this->y = $a->y - $b->y;
        return $this;
    }

    public function multiply($v)
    {
        $this->x *= $v->x;
        $this->y *= $v->y;
        return $this;
    }

    public function multiplyScalar($scalar)
    {
        $this->x *= $scalar;
        $this->y *= $scalar;
        return $this;
    }

    public function divide($v)
    {
        $this->x /= $v->x;
        $this->y /= $v->y;
        return $this;
    }

    public function divideScalar($scalar)
    {
        return $this->multiplyScalar(1 / $scalar);
    }

    public function applyMatrix3(Matrix3 $m)
    {
        $x = $this->x;
        $y = $this->y;
        $e = $m->elements;
        $this->x = $e[0] * $x + $e[3] * $y + $e[6];
        $this->y = $e[1] * $x + $e[4] * $y + $e[7];
        return $this;
    }

    public function min($v)
    {
        $this->x = min($this->x, $v->x);
        $this->y = min($this->y, $v->y);
        return $this;
    }

    public function max($v)
    {
        $this->x = max($this->x, $v->x);
        $this->y = max($this->y, $v->y);
        return $this;
    }

    public function clamp($min, $max)
    {
        $this->x = max($min->x, min($max->x, $this->x));
        $this->y = max($min->y, min($max->y, $this->y));
        return $this;
    }

    public function clampScalar($minVal, $maxVal)
    {
        $this->x = max($minVal, min($maxVal, $this->x));
        $this->y = max($minVal, min($maxVal, $this->y));
        return $this;
    }

    public function clampLength($min, $max)
    {
        $length = $this->length();
        return $this->divideScalar($length ?: 1)->multiplyScalar(max($min, min($max, $length)));
    }

    public function floor()
    {
        $this->x = floor($this->x);
        $this->y = floor($this->y);
        return $this;
    }

    public function ceil()
    {
        $this->x = ceil($this->x);
        $this->y = ceil($this->y);
        return $this;
    }

    public function round()
    {
        $this->x = round($this->x);
        $this->y = round($this->y);
        return $this;
    }

    public function roundToZero()
    {
        $this->x = (int)$this->x;
        $this->y = (int)$this->y;
        return $this;
    }

    public function negate()
    {
        $this->x = -$this->x;
        $this->y = -$this->y;
        return $this;
    }

    public function dot($v)
    {
        return $this->x * $v->x + $this->y * $v->y;
    }

    public function cross($v)
    {
        return $this->x * $v->y - $this->y * $v->x;
    }

    public function lengthSq()
    {
        return $this->x * $this->x + $this->y * $this->y;
    }

    public function length()
    {
        return sqrt($this->x * $this->x + $this->y * $this->y);
    }

    public function manhattanLength()
    {
        return abs($this->x) + abs($this->y);
    }

    public function normalize()
    {
        $length = $this->length();
        return $this->divideScalar($length ?: 1);
    }

    public function angle()
    {
        $angle = atan2(-$this->y, -$this->x) + M_PI;
        return $angle;
    }

    public function angleTo($v)
    {
        $denominator = sqrt($this->lengthSq() * $v->lengthSq());
        if ($denominator === 0) return M_PI / 2;
        $theta = $this->dot($v) / $denominator;
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
        return $dx * $dx + $dy * $dy;
    }

    public function manhattanDistanceTo($v)
    {
        return abs($this->x - $v->x) + abs($this->y - $v->y);
    }

    public function setLength($length)
    {
        return $this->normalize()->multiplyScalar($length);
    }

    public function lerp($v, $alpha)
    {
        $this->x += ($v->x - $this->x) * $alpha;
        $this->y += ($v->y - $this->y) * $alpha;
        return $this;
    }

    public function lerpVectors($v1, $v2, $alpha)
    {
        $this->x = $v1->x + ($v2->x - $v1->x) * $alpha;
        $this->y = $v1->y + ($v2->y - $v1->y) * $alpha;
        return $this;
    }

    public function equals($v)
    {
        return $v->x == $this->x && $v->y == $this->y;
    }

    public function fromArray($array, $offset = 0)
    {
        $this->x = $array[$offset];
        $this->y = $array[$offset + 1];
        return $this;
    }

    public function toArray($array = [], $offset = 0)
    {
        $array[$offset] = $this->x;
        $array[$offset + 1] = $this->y;
        return $array;
    }

    public function fromBufferAttribute($attribute, $index)
    {
        $this->x = $attribute->getX($index);
        $this->y = $attribute->getY($index);
        return $this;
    }

    public function rotateAround($center, $angle)
    {
        $c = cos($angle);
        $s = sin($angle);
        $x = $this->x - $center->x;
        $y = $this->y - $center->y;
        $this->x = $x * $c - $y * $s + $center->x;
        $this->y = $x * $s + $y * $c + $center->y;
        return $this;
    }

    public function random()
    {
        $this->x = rand() / getrandmax();
        $this->y = rand() / getrandmax();
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
        return $this->position < 2;
    }
}

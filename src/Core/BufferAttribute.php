<?php

namespace ThreePHP\Core;

use Countable;
use ThreePHP\Math\MathUtils;
use ThreePHP\Math\Matrix3;
use ThreePHP\Math\Matrix4;
use ThreePHP\Math\Vector3;

class BufferAttribute implements Countable
{
    public readonly bool $isBufferAttribute;
    public string $name = '';

    public array $array = [];
    public int $itemSize = 1;
    public int $count = 0;
    public bool $normalized = false;

    public $usage = null;
    public $updateRange = null;
    public $gpuType = null;
    public $version = 0;

    public function __construct(array $array, $itemSize, $normalized = false)
    {
        $this->isBufferAttribute = true;
        $this->array = $array;
        $this->itemSize = $itemSize;
        $this->count = count($array) / $itemSize;
        $this->normalized = $normalized;
    }

    public function __set($property, $value): void
    {
        if ($property === 'needsUpdate' && $value === true) {
            $this->version++;
        }
    }

    public function setUsage($value)
    {
        $this->usage = $value;
        return $this;
    }

    public function copy($source)
    {

        $this->name = $source->name;
        $this->array = $source->array;
        $this->itemSize = $source->itemSize;
        $this->count = $source->count;
        $this->normalized = $source->normalized;
        $this->usage = $source->usage;

        return $this;
    }

    public function getX($index)
    {
        $x = $this->array[$index * $this->itemSize];
        if ($this->normalized) {
            $x = MathUtils::denormalize($x, $this->array);
        }
        return $x;
    }

    public function getY($index)
    {
        $y = $this->array[$index * $this->itemSize + 1];
        if ($this->normalized) {
            $y = MathUtils::denormalize($y, $this->array);
        }
        return $y;
    }

    public function getZ($index)
    {
        $z = $this->array[$index * $this->itemSize + 2];
        if ($this->normalized) {
            $z = MathUtils::denormalize($z, $this->array);
        }
        return $z;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function applyMatrix4(Matrix4 $m)
    {
        $vector = new Vector3();
        for ($i = 0; $i < $this->count; $i++) {
            $vector->fromBufferAttribute($this, $i);
            $vector->applyMatrix4($m);
            $this->setXYZ($i, $vector->x, $vector->y, $vector->z);
        }
        return $this;
    }

    public function applyNormalMatrix(Matrix3 $m)
    {
        $vector = new Vector3();

        for ($i = 0; $i < $this->count; $i++) {
            $vector->fromBufferAttribute($this, $i);
            $vector->applyNormalMatrix($m);
            $this->setXYZ($i, $vector->x, $vector->y, $vector->z);
        }

        return $this;
    }

    public function setXYZ($index, $x, $y, $z)
    {
        $index *= $this->itemSize;

        if ($this->normalized) {
            $x = MathUtils::normalize($x, $this->array);
            $y = MathUtils::normalize($y, $this->array);
            $z = MathUtils::normalize($z, $this->array);
        }

        $this->array[$index + 0] = $x;
        $this->array[$index + 1] = $y;
        $this->array[$index + 2] = $z;

        return $this;
    }
}

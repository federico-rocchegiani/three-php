<?php

namespace ThreePHP\Core;

use stdClass;
use ThreePHP\Events\AddedEvent;
use ThreePHP\Events\RemovedEvent;
use ThreePHP\Math\Euler;
use ThreePHP\Math\MathUtils;
use ThreePHP\Math\Matrix3;
use ThreePHP\Math\Matrix4;
use ThreePHP\Math\Quaternion;
use ThreePHP\Math\Vector2;
use ThreePHP\Math\Vector3;

class Object3D extends EventDispatcher
{
    const DEFAULT_MATRIX_AUTO_UPDATE = true;
    const DEFAULT_MATRIX_WORLD_AUTO_UPDATE = true;

    // public static $DEFAULT_UP = new Vector3(0, 1, 0);
    private static $object3DId = 0;

    public readonly bool $isObject3D;
    public readonly int $id;
    public readonly string $uuid;

    public string $type;
    public string $name = '';
    public ?Object3D $parent = null;
    /**
     * @var Object3D[]
     */
    public array $children = [];
    public Vector3 $up;
    public Vector3 $position;
    public Euler $rotation;
    public Quaternion $quaternion;
    public Vector3 $scale;
    public Matrix4 $modelViewMatrix;
    public Matrix3 $normalMatrix;
    public Matrix4 $matrix;
    public Matrix4 $matrixWorld;
    public Layers $layers;

    public bool $matrixAutoUpdate = self::DEFAULT_MATRIX_AUTO_UPDATE;
    public bool $matrixWorldAutoUpdate = self::DEFAULT_MATRIX_WORLD_AUTO_UPDATE;
    public bool $matrixWorldNeedsUpdate = false;
    public bool $visible = true;
    public bool $castShadow = false;
    public bool $receiveShadow = false;
    public bool $frustumCulled = true;
    public int $renderOrder = 0;
    public array $animations = [];
    public ?stdClass $userData;


    public function __construct()
    {
        $this->isObject3D = true;
        $this->type = 'Object3D';

        $this->id = static::$object3DId++;
        $this->uuid = MathUtils::uuid();

        $this->up = new Vector3(0, 1, 0);
        $this->position = new Vector3();
        $this->rotation = new Euler();
        $this->quaternion = new Quaternion();
        $this->scale = new Vector3(1, 1, 1);
        $this->modelViewMatrix = new Matrix4();
        $this->normalMatrix = new Matrix3();
        $this->matrix = new Matrix4();
        $this->matrixWorld = new Matrix4();
        $this->layers = new Layers();
        $this->userData = new stdClass();

        $this->rotation->setChangeCallback([$this, 'onRotationChange']);
        $this->quaternion->setChangeCallback([$this, 'onQuaternionChange']);
    }

    public function onRotationChange()
    {
        $this->quaternion->setFromEuler($this->rotation, false);
    }

    public function onQuaternionChange()
    {
        $this->rotation->setFromQuaternion($this->quaternion, null, false);
    }

    public function add($object)
    {
        if (func_num_args() > 1) {
            foreach (func_get_args() as $arg) {
                $this->add($arg);
            }
            return $this;
        }

        if (is_array($object)) {
            foreach ($object as $o) {
                $this->add($o);
            }
            return $this;
        }

        if ($object === $this) {
            throw new \Exception('ThreePHP.Object3D.add: object can\'t be added as a child of itself.' . var_export($object, true));
            return $this;
        }

        if ($object && $object instanceof Object3D) {
            if ($object->parent !== null) {
                $object->parent->remove($object);
            }
            $object->parent = $this;
            $this->children[] = $object;
            $object->dispatchEvent(new AddedEvent());
        } else {
            throw new \Exception('THREE.Object3D.add: object not an instance of THREE.Object3D.' . var_export($object, true));
        }

        return $this;
    }

    public function remove($object)
    {
        if (func_num_args() > 1) {
            foreach (func_get_args() as $arg) {
                $this->remove($arg);
            }
            return $this;
        }

        if (is_array($object)) {
            foreach ($object as $o) {
                $this->remove($o);
            }
            return $this;
        }

        $index = array_search($object, $this->children);

        if ($index !== false) {
            $object->parent = null;
            array_splice($this->children, $index, 1);
            $object->dispatchEvent(new RemovedEvent());
        }

        return $this;
    }

    public function traverse(callable $callback)
    {
        $callback($this);

        $children = $this->children;

        for ($i = 0, $l = count($children); $i < $l; $i++) {
            $children[$i]->traverse($callback);
        }
    }

    public function translateOnAxis($axis, $distance)
    {
        $v = $axis->clone()->applyQuaternion($this->quaternion);
        $this->position->add($v->multiplyScalar($distance));
        return $this;
    }

    public function translateX($distance)
    {
        return $this->translateOnAxis(Vector3::xAxis(), $distance);
    }

    public function translateY($distance)
    {
        return $this->translateOnAxis(Vector3::yAxis(), $distance);
    }

    public function translateZ($distance)
    {
        return $this->translateOnAxis(Vector3::zAxis(), $distance);
    }

    public function rotateOnAxis($axis, $angle)
    {
        // Ruota l'oggetto sull'asse nello spazio oggetto
        // Si presume che l'asse sia normalizzato

        $q = (new Quaternion())->setFromAxisAngle($axis, $angle);
        $this->quaternion->multiply($q);

        return $this;
    }

    public function rotateOnWorldAxis($axis, $angle)
    {
        // Ruota l'oggetto sull'asse nello spazio mondiale
        // Si presume che l'asse sia normalizzato
        // Questo metodo non tiene conto di genitori ruotati

        $q = (new Quaternion())->setFromAxisAngle($axis, $angle);
        $this->quaternion->premultiply($q);

        return $this;
    }

    public function rotateX($angle)
    {
        return $this->rotateOnAxis(Vector3::xAxis(), $angle);
    }

    public function rotateY($angle)
    {
        return $this->rotateOnAxis(Vector3::yAxis(), $angle);
    }

    public function rotateZ($angle)
    {
        return $this->rotateOnAxis(Vector3::zAxis(), $angle);
    }

    public function updateMatrix()
    {

        $this->matrix->compose($this->position, $this->quaternion, $this->scale);

        $this->matrixWorldNeedsUpdate = true;
    }

    public function updateMatrixWorld($force = false)
    {
        if ($this->matrixAutoUpdate) {
            $this->updateMatrix();
        }

        if ($this->matrixWorldNeedsUpdate || $force) {
            if ($this->parent === null) {
                $this->matrixWorld->copy($this->matrix);
            } else {
                $this->matrixWorld->multiplyMatrices($this->parent->matrixWorld, $this->matrix);
            }
            $this->matrixWorldNeedsUpdate = false;
            $force = true;
        }

        // update children

        $children = &$this->children;
        $l = count($children);
        for ($i = 0; $i < $l; $i++) {
            $child = $children[$i];
            if ($child->matrixWorldAutoUpdate === true || $force === true) {
                $child->updateMatrixWorld($force);
            }
        }
    }
}

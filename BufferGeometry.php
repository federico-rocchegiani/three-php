<?php

namespace ThreePHP\Core;

use stdClass;
use ThreePHP\Buffers\Uint16BufferAttribute;
use ThreePHP\Buffers\Uint32BufferAttribute;
use ThreePHP\Math\MathUtils;
use ThreePHP\Math\Matrix3;
use ThreePHP\Math\Vector3;

use function ThreePHP\arrayNeedsUint32;

class BufferGeometry extends EventDispatcher
{
    private static $object3DId = 0;
    public readonly bool $isBufferGeometry;
    public readonly int $id;
    public readonly string $uuid;
    public string $type;
    public string $name = '';

    public $index = null;
    public $attributes;
    public $morphAttributes;
    public $morphTargetsRelative;
    public $groups;
    public ?Box3 $boundingBox;
    public $boundingSphere;
    public $drawRange;
    public $userData;

    public $parameters;

    public function __construct()
    {
        $this->isBufferGeometry = true;
        $this->type = 'Object3D';

        $this->id = static::$object3DId++;
        $this->uuid = MathUtils::uuid();

        $this->index = null;
        $this->attributes =  new stdClass();
        $this->morphAttributes = new stdClass();
        $this->morphTargetsRelative = false;
        $this->groups = [];
        $this->boundingBox = null;
        $this->boundingSphere = null;
        $this->userData = new stdClass();

        $this->drawRange = new stdClass();
        $this->drawRange->start = 0;
        $this->drawRange->count = INF;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setIndex($index)
    {
        if (is_array($index)) {
            // $this->index = new (arrayNeedsUint32($index) ? Uint32BufferAttribute : Uint16BufferAttribute)($index, 1);
            $this->index = new Uint32BufferAttribute($index, 1);
        } else {

            $this->index = $index;
        }

        return $this;
    }

    public function getAttribute($name)
    {
        return $this->attributes->{$name} ?? null;
    }

    public function setAttribute($name, $attribute)
    {
        $this->attributes->{$name} = $attribute;
        return $this;
    }

    public function addGroup($start, $count, $materialIndex = 0)
    {
        $this->groups[] = [
            'start' => $start,
            'count' => $count,
            'materialIndex' => $materialIndex,
        ];
        return $this;
    }

    public function applyMatrix4($matrix)
    {
        $position = $this->getAttribute('position');

        if ($position !== null) {
            $position->applyMatrix4($matrix);
            $position->needsUpdate = true;
        }

        $normal = $this->getAttribute('normal');

        if ($normal !== null) {
            $normalMatrix = (new Matrix3())->getNormalMatrix($matrix);
            $normal->applyNormalMatrix($normalMatrix);
            $normal->needsUpdate = true;
        }

        $tangent = $this->getAttribute('tangent');

        if ($tangent !== null) {
            $tangent->transformDirection($matrix);
            $tangent->needsUpdate = true;
        }

        if ($this->boundingBox !== null) {
            $this->computeBoundingBox();
        }

        if ($this->boundingSphere !== null) {
            $this->computeBoundingSphere();
        }

        return $this;
    }

    public function computeBoundingBox()
    {
        if ($this->boundingBox === null) {
            $this->boundingBox = new Box3();
        }

        $position = $this->attributes->position;
        $morphAttributesPosition = $this->morphAttributes->position ?? null;

        if ($position !== null && property_exists($position, 'isGLBufferAttribute')) {
            // In PHP, we cannot directly translate this GLBufferAttribute check.
            // You may need to handle GLBufferAttribute separately in your PHP code.
            echo 'THREE.BufferGeometry.computeBoundingBox(): GLBufferAttribute requires a manual bounding box. Alternatively set "mesh.frustumCulled" to "false".';
            $this->boundingBox->set(new Vector3(-INF, -INF, -INF), new Vector3(INF, INF, INF));
            return;
        }

        if ($position !== null) {
            $this->boundingBox->setFromBufferAttribute($position);

            if ($morphAttributesPosition) {
                foreach ($morphAttributesPosition as $morphAttribute) {
                    $box = $morphAttribute->computeBoundingBox();
                    if ($this->morphTargetsRelative) {
                        $this->boundingBox->expandByVector($this->boundingBox->min->add($box->min));
                        $this->boundingBox->expandByVector($this->boundingBox->max->add($box->max));
                    } else {
                        $this->boundingBox->expandByPoint($box->min);
                        $this->boundingBox->expandByPoint($box->max);
                    }
                }
            }
        } else {
            $this->boundingBox->makeEmpty();
        }

        if (is_nan($this->boundingBox->min->x) || is_nan($this->boundingBox->min->y) || is_nan($this->boundingBox->min->z)) {
            echo 'THREE.BufferGeometry.computeBoundingBox(): Computed min/max have NaN values. The "position" attribute is likely to have NaN values.';
        }
    }

    public function computeBoundingSphere()
    {
    }

    public function computeVertexNormals()
    {
        $index = $this->index;
        $positionAttribute = $this->getAttribute('position');
        if ($positionAttribute !== null) {
            $normalAttribute = $this->getAttribute('normal');
            if ($normalAttribute === null) {
                $normalAttribute = new BufferAttribute([], 3);
                $this->setAttribute('normal', $normalAttribute);
            } else {
                // reset existing normals to zero
                for ($i = 0, $il = $normalAttribute->count; $i < $il; $i++) {
                    $normalAttribute->setXYZ($i, 0, 0, 0);
                }
            }
            $pA = new Vector3();
            $pB = new Vector3();
            $pC = new Vector3();
            $nA = new Vector3();
            $nB = new Vector3();
            $nC = new Vector3();
            $cb = new Vector3();
            $ab = new Vector3();
            // indexed elements
            if ($index !== null) {
                for ($i = 0, $il = $index->count; $i < $il; $i += 3) {
                    $vA = $index->getX($i + 0);
                    $vB = $index->getX($i + 1);
                    $vC = $index->getX($i + 2);
                    $pA->fromBufferAttribute($positionAttribute, $vA);
                    $pB->fromBufferAttribute($positionAttribute, $vB);
                    $pC->fromBufferAttribute($positionAttribute, $vC);
                    $cb->subVectors($pC, $pB);
                    $ab->subVectors($pA, $pB);
                    $cb->cross($ab);
                    $nA->fromBufferAttribute($normalAttribute, $vA);
                    $nB->fromBufferAttribute($normalAttribute, $vB);
                    $nC->fromBufferAttribute($normalAttribute, $vC);
                    $nA->add($cb);
                    $nB->add($cb);
                    $nC->add($cb);
                    $normalAttribute->setXYZ($vA, $nA->x, $nA->y, $nA->z);
                    $normalAttribute->setXYZ($vB, $nB->x, $nB->y, $nB->z);
                    $normalAttribute->setXYZ($vC, $nC->x, $nC->y, $nC->z);
                }
            } else {
                // non-indexed elements (unconnected triangle soup)
                for ($i = 0, $il = $positionAttribute->count; $i < $il; $i += 3) {
                    $pA->fromBufferAttribute($positionAttribute, $i + 0);
                    $pB->fromBufferAttribute($positionAttribute, $i + 1);
                    $pC->fromBufferAttribute($positionAttribute, $i + 2);
                    $cb->subVectors($pC, $pB);
                    $ab->subVectors($pA, $pB);
                    $cb->cross($ab);
                    $normalAttribute->setXYZ($i + 0, $cb->x, $cb->y, $cb->z);
                    $normalAttribute->setXYZ($i + 1, $cb->x, $cb->y, $cb->z);
                    $normalAttribute->setXYZ($i + 2, $cb->x, $cb->y, $cb->z);
                }
            }
            $this->normalizeNormals();
            $normalAttribute->needsUpdate = true;
        }
    }

    public function normalizeNormals()
    {
        $normals = $this->attributes->normal;
        $vector = new Vector3();

        for ($i = 0, $il = count($normals); $i < $il; $i++) {
            $vector->fromBufferAttribute($normals, $i);
            $vector->normalize();
            $normals->setXYZ($i, $vector->x, $vector->y, $vector->z);
        }
    }

    public function toJSON()
    {
        $data = [
            'metadata' => [
                'version' => 4.5,
                'type' => 'BufferGeometry',
                'generator' => 'BufferGeometry.toJSON'
            ]
        ];

        // Serializzazione standard di BufferGeometry
        $data['uuid'] = $this->uuid;
        $data['type'] = $this->type;
        if ($this->name !== '') $data['name'] = $this->name;
        if (count(get_object_vars($this->userData)) > 0) $data['userData'] = $this->userData;
        if ($this->parameters !== null) {
            $parameters = $this->parameters;
            foreach ($parameters as $key => $value) {
                if ($value !== null) $data[$key] = $value;
            }
            return $data;
        }

        // Per semplicità, il codice assume che gli attributi non siano condivisi tra geometrie, vedere #15811
        $data['data'] = ['attributes' => []];
        $index = $this->index;
        if ($index !== null) {
            $data['data']['index'] = [
                'type' => get_class($index->array),
                'array' => array_slice($index->array, 0)
            ];
        }

        $attributes = $this->attributes;
        foreach ($attributes as $key => $attribute) {
            $data['data']['attributes'][$key] = $attribute->toJSON($data['data']);
        }

        $morphAttributes = [];
        $hasMorphAttributes = false;

        foreach ($this->morphAttributes as $key => $attributeArray) {
            $array = [];
            foreach ($attributeArray as $attribute) {
                $array[] = $attribute->toJSON($data['data']);
            }
            if (count($array) > 0) {
                $morphAttributes[$key] = $array;
                $hasMorphAttributes = true;
            }
        }

        if ($hasMorphAttributes) {
            $data['data']['morphAttributes'] = $morphAttributes;
            $data['data']['morphTargetsRelative'] = $this->morphTargetsRelative;
        }

        $groups = $this->groups;
        if (count($groups) > 0) {
            $data['data']['groups'] = json_decode(json_encode($groups), true);
        }

        $boundingSphere = $this->boundingSphere;
        if ($boundingSphere !== null) {
            $data['data']['boundingSphere'] = [
                'center' => $boundingSphere->center->toArray(),
                'radius' => $boundingSphere->radius
            ];
        }

        return $data;
    }
}

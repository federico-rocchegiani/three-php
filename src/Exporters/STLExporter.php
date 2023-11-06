<?php

namespace ThreePHP\Exporters;

use Exception;
use ThreePHP\Core\Object3D;
use ThreePHP\Math\Vector3;

class STLExporter
{
    public static function parse(Object3D $scene, array $options = [])
    {
        $binary = $options['binary'] ?? false;
        $objects = [];
        $triangles = 0;

        $scene->traverse(function ($object) use (&$objects, &$triangles) {
            if (!property_exists($object, 'isMesh')) {
                return;
            }
            /* @var $object \ThreePHP\Objects\Mesh */
            $geometry = $object->geometry;
            $index = $geometry->index;
            $positionAttribute = $geometry->getAttribute('position');
            $triangles += count($index ?? $positionAttribute) / 3;

            $objects[] = [
                'object3d' => $object,
                'geometry' => $geometry,
            ];
        });

        $output = '';
        $offset = 80;

        if ($binary) {
            throw new \Exception('Not implemented yet');
            // $bufferLength = $triangles * 2 + $triangles * 3 * 4 * 4 + 80 + 4;
            // $arrayBuffer = str_repeat(chr(0), $bufferLength);
            // $output = new DataView($arrayBuffer);
            // $output->setUint32($offset, $triangles, true);
            // $offset += 4;
        } else {
            $output = "solid exported\n";
        }

        $il = count($objects);

        for ($i = 0; $i < $il; $i++) {

            $object = $objects[$i]['object3d'];
            $geometry = $objects[$i]['geometry'];

            $index = $geometry->index;
            $positionAttribute = $geometry->getAttribute('position');

            if ($index !== null) {
                // indexed geometry
                for ($j = 0; $j < $index->count; $j += 3) {
                    $a = $index->getX($j + 0);
                    $b = $index->getX($j + 1);
                    $c = $index->getX($j + 2);
                    static::writeFace($output, $a, $b, $c, $positionAttribute, $object);
                }
            } else {
                // non-indexed geometry
                for ($j = 0; $j < $positionAttribute->count; $j += 3) {
                    $a = $j + 0;
                    $b = $j + 1;
                    $c = $j + 2;
                    static::writeFace($output, $a, $b, $c, $positionAttribute, $object);
                }
            }
        }

        if ($binary === false) {
            $output .= "endsolid exported\n";
        }

        return $output;
    }


    private static function writeFace(&$output, $a, $b, $c, $positionAttribute, $object, $binary = false)
    {
        $vA = (new Vector3())->fromBufferAttribute($positionAttribute, $a);
        $vB = (new Vector3())->fromBufferAttribute($positionAttribute, $b);
        $vC = (new Vector3())->fromBufferAttribute($positionAttribute, $c);

        if (property_exists($object, 'isSkinnedMesh')) {
            $object->boneTransform($a, $vA);
            $object->boneTransform($b, $vB);
            $object->boneTransform($c, $vC);
        }

        $vA->applyMatrix4($object->matrixWorld);
        $vB->applyMatrix4($object->matrixWorld);
        $vC->applyMatrix4($object->matrixWorld);

        static::writeNormal($output, $vA, $vB, $vC, $binary);

        static::writeVertex($output, $vA, $binary);
        static::writeVertex($output, $vB, $binary);
        static::writeVertex($output, $vC, $binary);

        if ($binary === true) {
            throw new \Exception('Not implemented yet');
            //output.setUint16(offset, 0, true); offset += 2;
        } else {
            $output .= "\t\tendloop\n";
            $output .= "\tendfacet\n";
        }
    }

    private static function writeNormal(&$output, $vA, $vB, $vC, $binary = false)
    {

        $cb = (new Vector3())->subVectors($vC, $vB);
        $ab = (new Vector3())->subVectors($vA, $vB);
        $cb = (new Vector3())->cross($ab)->normalize();

        $normal = (new Vector3())->copy($cb)->normalize();


        if ($binary === true) {
            throw new \Exception('Not implemented yet');
            // output.setFloat32(offset, normal.x, true); offset += 4;
            // output.setFloat32(offset, normal.y, true); offset += 4;
            // output.setFloat32(offset, normal.z, true); offset += 4;
        } else {
            $output .= "\tfacet normal " . $normal->x . " " . $normal->y . " " . $normal->z . "\n";
            $output .= "\t\touter loop\n";
        }
    }

    private static function writeVertex(&$output, $vertex, $binary = false)
    {

        if ($binary === true) {
            throw new \Exception('Not implemented yet');
            // output.setFloat32(offset, vertex.x, true); offset += 4;
            // output.setFloat32(offset, vertex.y, true); offset += 4;
            // output.setFloat32(offset, vertex.z, true); offset += 4;
        } else {
            $output .= "\t\t\tvertex " . $vertex->x . " " . $vertex->y . " " . $vertex->z . "\n";
        }
    }
}

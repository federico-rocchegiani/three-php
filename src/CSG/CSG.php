<?php

namespace ThreePHP\CSG;

use ThreePHP\Buffers\Float32BufferAttribute;
use ThreePHP\Core\BufferGeometry;
use ThreePHP\Materials\Material;
use ThreePHP\Materials\MeshBasicMaterial;
use ThreePHP\Math\Matrix3;
use ThreePHP\Math\Matrix4;
use ThreePHP\Math\Quaternion;
use ThreePHP\Math\Vector2;
use ThreePHP\Math\Vector3;
use ThreePHP\Objects\Mesh;

class CSG
{

    public $polygons;

    public function __construct(array $poligons = [])
    {
        $this->polygons = $poligons;
    }

    public function clone()
    {
        $csg = new CSG();
        $csg->polygons = array_map(fn ($p) => $p->clone(), $this->polygons);
        return $csg;
    }

    public function toPolygons()
    {
        return $this->polygons;
    }

    public function union($csg)
    {
        $a = new Node($this->clone()->polygons);
        $b = new Node($csg->clone()->polygons);
        $a->clipTo($b);
        $b->clipTo($a);
        $b->invert();
        $b->clipTo($a);
        $b->invert();
        $a->build($b->allPolygons());
        return CSG::fromPolygons($a->allPolygons());
    }

    public function subtract($csg)
    {
        $a = new Node($this->clone()->polygons);
        $b = new Node($csg->clone()->polygons);
        $a->invert();
        $a->clipTo($b);
        $b->clipTo($a);
        $b->invert();
        $b->clipTo($a);
        $b->invert();
        $a->build($b->allPolygons());
        $a->invert();
        return CSG::fromPolygons($a->allPolygons());
    }

    public function intersect($csg)
    {
        $a = new Node($this->clone()->polygons);
        $b = new Node($csg->clone()->polygons);
        $a->invert();
        $b->clipTo($a);
        $b->invert();
        $a->clipTo($b);
        $b->clipTo($a);
        $a->build($b->allPolygons());
        $a->invert();
        return CSG::fromPolygons($a->allPolygons());
    }
    // Return a new CSG solid with solid and empty space switched. This solid is
    // not modified.
    public function inverse()
    {
        $csg = $this->clone();
        array_walk($csg->polygons, fn (&$p) => $p->flip());
        return $csg;
    }


    public function toGeometry()
    {
        $geometry = new BufferGeometry();

        $vertices = [];
        $normals = [];
        $uvs = null;
        $colors = null;

        $vertices_count = 0;
        $normals_count = 0;
        $uvs_count = 0;
        $colors_count = 0;

        $groups = [];

        foreach ($this->polygons as $p) {
            /** @var Vertex[] $pvs */
            $pvs = $p->vertices;
            $pvlen = count($pvs);

            if ($p->shared !== null) {
                if (!isset($groups[$p->shared])) {
                    $groups[$p->shared] = [];
                }
            }

            if ($pvlen) {
                if (isset($pvs[0]->color)) {
                    if ($colors === null) {
                        $colors = [];
                    }
                }
                if (isset($pvs[0]->uv)) {
                    if ($uvs === null) {
                        $uvs = [];
                    }
                }
            }

            for ($j = 3; $j <= $pvlen; $j++) {
                if (isset($p->shared)) {
                    $tmp = $vertices_count / 3;
                    array_push($groups[$p->shared], $tmp, $tmp + 1, $tmp + 2);
                }

                $pvs[0]->pos->toArray($vertices, $vertices_count);
                $pvs[$j - 2]->pos->toArray($vertices, $vertices_count + 3);
                $pvs[$j - 1]->pos->toArray($vertices, $vertices_count + 6);
                $vertices_count += 9;

                $pvs[0]->normal->toArray($normals, $normals_count);
                $pvs[$j - 2]->normal->toArray($normals, $normals_count + 3);
                $pvs[$j - 1]->normal->toArray($normals, $normals_count + 6);
                $normals_count += 9;

                if ($uvs !== null && isset($pvs[0]->uv)) {
                    $pvs[0]->uv->toArray($uvs, $uvs_count);
                    $pvs[$j - 2]->uv->toArray($uvs, $uvs_count + 2);
                    $pvs[$j - 1]->uv->toArray($uvs, $uvs_count + 4);
                    $uvs_count += 6;
                }

                if ($colors !== null) {
                    $pvs[0]->color->toArray($colors, $colors_count);
                    $pvs[$j - 2]->color->toArray($colors, $colors_count + 3);
                    $pvs[$j - 1]->color->toArray($colors, $colors_count + 6);
                    $colors_count += 9;
                }
            }
        }

        $geometry->setAttribute('position', new Float32BufferAttribute($vertices, 3));
        $geometry->setAttribute('normal', new Float32BufferAttribute($normals, 3));

        if ($uvs !== null) {
            $geometry->setAttribute('uv', new Float32BufferAttribute($uvs, 2));
        }

        if ($colors !== null) {
            $geometry->setAttribute('color', new Float32BufferAttribute($colors, 3));
        }

        $groupsData = [];

        foreach ($groups as $key => $group) {
            $geometry->addGroup(count($groupsData), count($group), $key);
            $groupsData = array_merge($groupsData, $group);
        }

        $geometry->setIndex($groupsData);

        return $geometry;
    }

    public function toMesh(Matrix4 $matrix = new Matrix4(), Material $material = new MeshBasicMaterial())
    {
        $geometry = $this->toGeometry();
        $inv = $matrix->clone()->invert();
        $geometry->applyMatrix4($inv);
        $geometry->computeBoundingSphere();
        $geometry->computeBoundingBox();
        $mesh = new Mesh($geometry, $material);
        $mesh->matrix->copy($matrix);
        $mesh->matrix->decompose($mesh->position, $mesh->quaternion, $mesh->scale);
        $mesh->rotation->setFromQuaternion($mesh->quaternion);
        $mesh->updateMatrixWorld();
        $mesh->castShadow = true;
        $mesh->receiveShadow = true;
        return $mesh;
    }

    public static function fromPolygons($poligons)
    {
        $csg = new CSG($poligons);
        return $csg;
    }

    public static function fromGeometry(BufferGeometry $geom, $objectIndex)
    {
        $posAttr = $geom->getAttribute('position');
        $normalAttr = $geom->getAttribute('normal');
        $uvAttr = $geom->getAttribute('uv');
        $colorAttr = $geom->getAttribute('color');
        $index = $geom->index ? $geom->index->array : range(0, (count($posAttr->array) / $posAttr->itemSize) - 1);

        $polys = [];
        $l = count($index);

        for ($i = 0, $pli = 0; $i < $l; $i += 3, $pli++) {
            $vertices = [];

            for ($j = 0; $j < 3; $j++) {
                $vi = $index[$i + $j];
                $vp = $vi * 3;
                $vt = $vi * 2;
                $x = $posAttr->array[$vp];
                $y = $posAttr->array[$vp + 1];
                $z = $posAttr->array[$vp + 2];
                $nx = $normalAttr->array[$vp];
                $ny = $normalAttr->array[$vp + 1];
                $nz = $normalAttr->array[$vp + 2];

                $vertices[$j] = new Vertex(
                    new Vector3($x, $y,  $z),
                    new Vector3($nx, $ny, $nz),
                    $uvAttr ? new Vector2($uvAttr->array[$vt], $uvAttr->array[$vt + 1]) : null,
                    $colorAttr ? new Vector3($colorAttr->array[$vt],  $colorAttr->array[$vt + 1],  $colorAttr->array[$vt + 2]) : null
                );
            }
            $polys[$pli] = new Polygon($vertices, $objectIndex);
        }

        return CSG::fromPolygons($polys);
    }

    public static function fromMesh(Mesh $mesh, $objectIndex)
    {
        $csg = CSG::fromGeometry($mesh->geometry, $objectIndex);
        $m = (new Matrix3())->getNormalMatrix($mesh->matrix);
        for ($i = 0; $i < count($csg->polygons); $i++) {
            $p = $csg->polygons[$i];
            for ($j = 0; $j < count($p->vertices); $j++) {
                $v = $p->vertices[$j];
                $v->pos->copy($v->pos->clone()->applyMatrix4($mesh->matrix));
                $v->normal->copy($v->normal->clone()->applyMatrix3($m));
            }
        }
        return $csg;
    }
}

/*
CSG.nbuf3 = (ct) => {
    return {
        top: 0,
        array: new Float32Array(ct),
        write: function (v) {
            // posso usare Vector3.toArray (?)
            $this->array[$this->top++] = v.x;
            $this->array[$this->top++] = v.y;
            $this->array[$this->top++] = v.z;
        },
    };
};
CSG.nbuf2 = (ct) => {
    return {
        top: 0,
        array: new Float32Array(ct),
        write: function (v) {
            $this->array[$this->top++] = v.x;
            $this->array[$this->top++] = v.y;
        },
    };
};

CSG.toMesh = function (csg, toMatrix, toMaterial) {
    let geom = CSG.toGeometry(csg);
    let inv = new THREE.Matrix4().copy(toMatrix).invert();
    geom.applyMatrix4(inv);
    geom.computeBoundingSphere();
    geom.computeBoundingBox();
    let m = new THREE.Mesh(geom, toMaterial);
    m.matrix.copy(toMatrix);
    m.matrix.decompose(m.position, m.quaternion, m.scale);
    m.rotation.setFromQuaternion(m.quaternion);
    m.updateMatrixWorld();
    m.castShadow = m.receiveShadow = true;
    return m;
};
*/
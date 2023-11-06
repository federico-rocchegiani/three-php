<?php

namespace ThreePHP\Objects;

use ThreePHP\Core\BufferGeometry;
use ThreePHP\Core\Object3D;
use ThreePHP\Materials\Material;
use ThreePHP\Materials\MeshBasicMaterial;

class Mesh extends Object3D
{
    public readonly bool $isMesh;

    public BufferGeometry $geometry;
    public Material $material;

    public function __construct(BufferGeometry $geometry = new BufferGeometry(), Material $material = new MeshBasicMaterial())
    {
        parent::__construct();

        $this->isMesh  = true;
        $this->type = 'Mesh';

        $this->geometry = $geometry;
        $this->material = $material;
    }
}

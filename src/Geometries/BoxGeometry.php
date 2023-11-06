<?php

namespace ThreePHP\Geometries;

use ThreePHP\Buffers\Float32BufferAttribute;
use ThreePHP\Core\BufferGeometry;
use ThreePHP\Math\Vector3;

class BoxGeometry extends BufferGeometry
{

    public function __construct($width = 1, $height = 1, $depth = 1, $widthSegments = 1, $heightSegments = 1, $depthSegments = 1)
    {
        parent::__construct();
        $this->type = 'BoxGeometry';

        $this->parameters = [
            'width' => $width,
            'height' => $height,
            'depth' => $depth,
            'widthSegments' => $widthSegments,
            'heightSegments' => $heightSegments,
            'depthSegments' => $depthSegments
        ];

        $widthSegments = floor($widthSegments);
        $heightSegments = floor($heightSegments);
        $depthSegments = floor($depthSegments);

        $indices = [];
        $vertices = [];
        $normals = [];
        $uvs = [];
        $numberOfVertices = 0;
        $groupStart = 0;

        $this->buildPlane($indices, $vertices, $normals, $uvs, $numberOfVertices, $groupStart, 'z', 'y', 'x', -1, -1, $depth, $height, $width, $depthSegments, $heightSegments, 0); // px
        $this->buildPlane($indices, $vertices, $normals, $uvs, $numberOfVertices, $groupStart, 'z', 'y', 'x', +1, -1, $depth, $height, -$width, $depthSegments, $heightSegments, 1); // nx
        $this->buildPlane($indices, $vertices, $normals, $uvs, $numberOfVertices, $groupStart, 'x', 'z', 'y', +1, +1, $width, $depth, $height, $widthSegments, $depthSegments, 2); // py
        $this->buildPlane($indices, $vertices, $normals, $uvs, $numberOfVertices, $groupStart, 'x', 'z', 'y', +1, -1, $width, $depth, -$height, $widthSegments, $depthSegments, 3); // ny
        $this->buildPlane($indices, $vertices, $normals, $uvs, $numberOfVertices, $groupStart, 'x', 'y', 'z', +1, -1, $width, $height, $depth, $widthSegments, $heightSegments, 4); // pz
        $this->buildPlane($indices, $vertices, $normals, $uvs, $numberOfVertices, $groupStart, 'x', 'y', 'z', -1, -1, $width, $height, -$depth, $widthSegments, $heightSegments, 5); // nz

        $this->setIndex($indices);
        $this->setAttribute('position', new Float32BufferAttribute($vertices, 3));
        $this->setAttribute('normal', new Float32BufferAttribute($normals, 3));
        $this->setAttribute('uv', new Float32BufferAttribute($uvs, 2));
    }

    protected function buildPlane(&$indices, &$vertices, &$normals, &$uvs, &$numberOfVertices, &$groupStart, $u, $v, $w, $udir, $vdir, $width, $height, $depth, $gridX, $gridY, $materialIndex)
    {
        $segmentWidth = $width / $gridX;
        $segmentHeight = $height / $gridY;
        $widthHalf = $width / 2;
        $heightHalf = $height / 2;
        $depthHalf = $depth / 2;
        $gridX1 = $gridX + 1;
        $gridY1 = $gridY + 1;
        $vertexCounter = 0;
        $groupCount = 0;
        $vector = new Vector3();

        // Generate vertices, normals, and uvs
        for ($iy = 0; $iy < $gridY1; $iy++) {
            $y = $iy * $segmentHeight - $heightHalf;
            for ($ix = 0; $ix < $gridX1; $ix++) {
                $x = $ix * $segmentWidth - $widthHalf;
                $vector->{$u} = $x * $udir;
                $vector->{$v} = $y * $vdir;
                $vector->{$w} = $depthHalf;
                $vertices[] = $vector->x;
                $vertices[] = $vector->y;
                $vertices[] = $vector->z;
                $vector->{$u} = 0;
                $vector->{$v} = 0;
                $vector->{$w} = $depth > 0 ? 1 : -1;
                $normals[] = $vector->x;
                $normals[] = $vector->y;
                $normals[] = $vector->z;
                $uvs[] = $ix / $gridX;
                $uvs[] = 1 - ($iy / $gridY);
                $vertexCounter += 1;
            }
        }

        // Indices
        for ($iy = 0; $iy < $gridY; $iy++) {
            for ($ix = 0; $ix < $gridX; $ix++) {
                $a = $numberOfVertices + $ix + $gridX1 * $iy;
                $b = $numberOfVertices + $ix + $gridX1 * ($iy + 1);
                $c = $numberOfVertices + ($ix + 1) + $gridX1 * ($iy + 1);
                $d = $numberOfVertices + ($ix + 1) + $gridX1 * $iy;
                // Faces
                $indices[] = $a;
                $indices[] = $b;
                $indices[] = $d;
                $indices[] = $b;
                $indices[] = $c;
                $indices[] = $d;
                $groupCount += 6;
            }
        }

        // Aggiungi un gruppo alla geometria per supportare materiali multipli
        $this->addGroup($groupStart, $groupCount, $materialIndex);

        // Calcola il nuovo valore di inizio per i gruppi
        $groupStart += $groupCount;

        // Aggiorna il numero totale di vertici
        $numberOfVertices += $vertexCounter;
    }
}

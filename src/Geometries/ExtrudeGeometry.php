<?php

namespace ThreePHP\Geometries;

use ThreePHP\Buffers\Float32BufferAttribute;
use ThreePHP\Core\BufferGeometry;
use ThreePHP\Math\MathUtils;
use ThreePHP\Math\Vector2;
use ThreePHP\Math\Vector3;
use ThreePHP\Paths\ShapeUtils;

class ExtrudeGeometry extends BufferGeometry
{

    public $verticesArray = [];
    public $uvArray = [];
    public $flen;
    public $vlen;
    public $bevelEnabled;
    public $bevelSegments;
    public $steps;
    public $faces;
    public $placeholder;
    public $uvgen;

    public function __construct($shapes = [new Vector2(0.5, 0.5), new Vector2(-0.5, 0.5), new Vector2(-0.5, -0.5), new Vector2(0.5, -0.5)], $options = [])
    {
        parent::__construct();
        $this->type = 'ExtrudeGeometry';
        $this->parameters = array(
            'shapes' => $shapes,
            'options' => $options
        );
        $shapes = is_array($shapes) ? $shapes : array($shapes);
        $this->verticesArray = [];
        $this->uvArray = [];
        for ($i = 0, $l = count($shapes); $i < $l; $i++) {
            $shape = $shapes[$i];
            $this->addShape($shape, $options);
        }
        // build geometry
        $this->setAttribute('position', new Float32BufferAttribute($this->verticesArray, 3));
        $this->setAttribute('uv', new Float32BufferAttribute($this->uvArray, 2));
        $this->computeVertexNormals();
    }

    public function addShape($shape, $options = [])
    {
        $this->placeholder = [];
        // Opzioni
        $curveSegments = isset($options['curveSegments']) ? $options['curveSegments'] : 12;
        $this->steps = isset($options['steps']) ? $options['steps'] : 1;
        $depth = isset($options['depth']) ? $options['depth'] : 1;
        $this->bevelEnabled = isset($options['bevelEnabled']) ? $options['bevelEnabled'] : true;
        $bevelThickness = isset($options['bevelThickness']) ? $options['bevelThickness'] : 0.2;
        $bevelSize = isset($options['bevelSize']) ? $options['bevelSize'] : $bevelThickness - 0.1;
        $bevelOffset = isset($options['bevelOffset']) ? $options['bevelOffset'] : 0;
        $this->bevelSegments = isset($options['bevelSegments']) ? $options['bevelSegments'] : 3;
        $extrudePath = isset($options['extrudePath']) ? $options['extrudePath'] : null;
        $this->uvgen = isset($options['UVGenerator']) ? $options['UVGenerator'] : 'ThreePHP\\WorldUVGenerator';
        //
        $extrudePts = [];
        $extrudeByPath = false;
        $splineTube = null;
        $binormal = new Vector3();
        $normal = new Vector3();
        $position2 = new Vector3();

        if ($extrudePath) {
            $extrudePts = $extrudePath->getSpacedPoints($this->steps);
            $extrudeByPath = true;
            $this->bevelEnabled = false; // bevels non supportati per l'estrusione del percorso
            // Imposta le variabili TNB
            // TODO1 - avere un .isClosed nello spline?
            $splineTube = $extrudePath->computeFrenetFrames($this->steps, false);
        }

        // Salvaguardie se i bevel non sono abilitati
        if (!$this->bevelEnabled) {
            $this->bevelSegments = 0;
            $bevelThickness = 0;
            $bevelSize = 0;
            $bevelOffset = 0;
        }

        // Inizializzazione delle variabili
        $shapePoints = $shape->extractPoints($curveSegments);
        $vertices = $shapePoints['shape'];
        $holes = $shapePoints['holes'];
        $reverse = !ShapeUtils::isClockWise($vertices);

        if ($reverse) {
            $vertices = array_reverse($vertices);
            // Forse dovremmo anche verificare se i fori sono nella direzione opposta, per sicurezza...
            foreach ($holes as $key => $ahole) {
                if (ShapeUtils::isClockWise($ahole)) {
                    $holes[$key] = array_reverse($ahole);
                }
            }
        }

        $this->faces = ShapeUtils::triangulateShape($vertices, $holes);

        /* Vertici */
        $contour = $vertices; // I vertici contengono tutti i punti, ma il contorno contiene solo i punti della circonferenza

        foreach ($holes as $ahole) {
            $vertices = array_merge($vertices, $ahole);
        }

        $this->vlen = count($vertices);
        $this->flen = count($this->faces);

        // Trova le direzioni per lo spostamento dei punti
        $contourMovements = [];

        for ($i = 0, $il = count($contour), $j = $il - 1, $k = $i + 1; $i < $il; $i++, $j++, $k++) {
            if ($j === $il) $j = 0;
            if ($k === $il) $k = 0;
            $contourMovements[$i] = $this->getBevelVec($contour[$i], $contour[$j], $contour[$k]);
        }

        $holesMovements = [];
        $oneHoleMovements = [];
        $verticesMovements = $contourMovements;

        foreach ($holes as $ahole) {
            $oneHoleMovements = [];

            for ($i = 0, $il = count($ahole), $j = $il - 1, $k = $i + 1; $i < $il; $i++, $j++, $k++) {
                if ($j === $il) $j = 0;
                if ($k === $il) $k = 0;
                $oneHoleMovements[$i] = $this->getBevelVec($ahole[$i], $ahole[$j], $ahole[$k]);
            }

            $holesMovements[] = $oneHoleMovements;
            $verticesMovements = array_merge($verticesMovements, $oneHoleMovements);
        }

        // Loop per i piani dei segmenti del bevel, 1 per il davanti, 1 per il retro
        for ($b = 0; $b < $this->bevelSegments; $b++) {
            $t = $b / $this->bevelSegments;
            $z = $bevelThickness * cos($t * M_PI / 2);
            $bs = $bevelSize * sin($t * M_PI / 2) + $bevelOffset;
            // Contrai la forma
            for ($i = 0, $il = count($contour); $i < $il; $i++) {
                $vert = $this->scalePt2($contour[$i], $contourMovements[$i], $bs);
                $this->v($vert->x, $vert->y, -$z);
            }
            // Espandi i fori
            foreach ($holes as $key => $ahole) {
                $oneHoleMovements = $holesMovements[$key];

                for ($i = 0, $il = count($ahole); $i < $il; $i++) {
                    $vert = $this->scalePt2($ahole[$i], $oneHoleMovements[$i], $bs);
                    $this->v($vert->x, $vert->y, -$z);
                }
            }
        }

        $bs = $bevelSize + $bevelOffset;

        // Vertici rivolti verso il retro
        for ($i = 0; $i < $this->vlen; $i++) {
            $vert = $this->bevelEnabled ? $this->scalePt2($vertices[$i], $verticesMovements[$i], $bs) : $vertices[$i];

            if (!$extrudeByPath) {
                $this->v($vert->x, $vert->y, 0);
            } else {
                $normal->copy($splineTube->normals[0])->multiplyScalar($vert->x);
                $binormal->copy($splineTube->binormals[0])->multiplyScalar($vert->y);
                $position2->copy($extrudePts[0])->add($normal)->add($binormal);
                $this->v($position2->x, $position2->y, $position2->z);
            }
        }

        // Aggiungi vertici graduali...
        // Inclusi i vertici rivolti verso il davanti
        for ($s = 1; $s <= $this->steps; $s++) {
            for ($i = 0; $i < $this->vlen; $i++) {
                $vert = $this->bevelEnabled ? $this->scalePt2($vertices[$i], $verticesMovements[$i], $bs) : $vertices[$i];

                if (!$extrudeByPath) {
                    $this->v($vert->x, $vert->y, $depth / $this->steps * $s);
                } else {
                    $normal->copy($splineTube->normals[$s])->multiplyScalar($vert->x);
                    $binormal->copy($splineTube->binormals[$s])->multiplyScalar($vert->y);
                    $position2->copy($extrudePts[$s])->add($normal)->add($binormal);
                    $this->v($position2->x, $position2->y, $position2->z);
                }
            }
        }

        // Aggiungi piani dei segmenti del bevel
        for ($b = $this->bevelSegments - 1; $b >= 0; $b--) {
            $t = $b / $this->bevelSegments;
            $z = $bevelThickness * cos($t * M_PI / 2);
            $bs = $bevelSize * sin($t * M_PI / 2) + $bevelOffset;
            // Contrai la forma
            for ($i = 0, $il = count($contour); $i < $il; $i++) {
                $vert = $this->scalePt2($contour[$i], $contourMovements[$i], $bs);
                $this->v($vert->x, $vert->y, $depth + $z);
            }
            // Espandi i fori
            foreach ($holes as $key => $ahole) {
                $oneHoleMovements = $holesMovements[$key];

                for ($i = 0, $il = count($ahole); $i < $il; $i++) {
                    $vert = $this->scalePt2($ahole[$i], $oneHoleMovements[$i], $bs);

                    if (!$extrudeByPath) {
                        $this->v($vert->x, $vert->y, $depth + $z);
                    } else {
                        $this->v($vert->x, $vert->y + $extrudePts[$this->steps - 1]->y, $extrudePts[$this->steps - 1]->x + $z);
                    }
                }
            }
        }

        /* Facce */
        // Facce superiori e inferiori
        $this->buildLidFaces();
        // Facce laterali
        $this->buildSideFaces($contour, $holes);
    }

    private function scalePt2($pt, $vec, $size)
    {
        if (!$vec) {
            echo ('THREE.ExtrudeGeometry: vec does not exist');
        }

        $clonedVec = $vec->clone();
        $scaledVec = $clonedVec->multiplyScalar($size);
        $resultPt = $scaledVec->add($pt);

        return $resultPt;
    }

    private function getBevelVec($inPt, $inPrev, $inNext)
    {
        $v_trans_x = 0;
        $v_trans_y = 0;
        $shrink_by = 0;

        $v_prev_x = $inPt->x - $inPrev->x;
        $v_prev_y = $inPt->y - $inPrev->y;

        $v_next_x = $inNext->x - $inPt->x;
        $v_next_y = $inNext->y - $inPt->y;

        $v_prev_lensq = ($v_prev_x * $v_prev_x + $v_prev_y * $v_prev_y);

        $collinear0 = ($v_prev_x * $v_next_y - $v_prev_y * $v_next_x);

        if (abs($collinear0) > PHP_FLOAT_EPSILON) {
            $v_prev_len = sqrt($v_prev_lensq);
            $v_next_len = sqrt($v_next_x * $v_next_x + $v_next_y * $v_next_y);

            $ptPrevShift_x = ($inPrev->x - $v_prev_y / $v_prev_len);
            $ptPrevShift_y = ($inPrev->y + $v_prev_x / $v_prev_len);
            $ptNextShift_x = ($inNext->x - $v_next_y / $v_next_len);
            $ptNextShift_y = ($inNext->y + $v_next_x / $v_next_len);

            $sf = (($ptNextShift_x - $ptPrevShift_x) * $v_next_y -
                ($ptNextShift_y - $ptPrevShift_y) * $v_next_x) /
                ($v_prev_x * $v_next_y - $v_prev_y * $v_next_x);

            $v_trans_x = $ptPrevShift_x + $v_prev_x * $sf - $inPt->x;
            $v_trans_y = $ptPrevShift_y + $v_prev_y * $sf - $inPt->y;

            $v_trans_lensq = ($v_trans_x * $v_trans_x + $v_trans_y * $v_trans_y);

            if ($v_trans_lensq <= 2) {
                return new Vector2($v_trans_x, $v_trans_y);
            } else {
                $shrink_by = sqrt($v_trans_lensq / 2);
            }
        } else {
            $direction_eq = false;

            if ($v_prev_x > PHP_FLOAT_EPSILON) {
                if ($v_next_x > PHP_FLOAT_EPSILON) {
                    $direction_eq = true;
                }
            } else {
                if ($v_prev_x < -PHP_FLOAT_EPSILON) {
                    if ($v_next_x < -PHP_FLOAT_EPSILON) {
                        $direction_eq = true;
                    }
                } else {
                    if (MathUtils::sign($v_prev_y) === MathUtils::sign($v_next_y)) {
                        $direction_eq = true;
                    }
                }
            }

            if ($direction_eq) {
                $v_trans_x = -$v_prev_y;
                $v_trans_y = $v_prev_x;
                $shrink_by = sqrt($v_prev_lensq);
            } else {
                $v_trans_x = $v_prev_x;
                $v_trans_y = $v_prev_y;
                $shrink_by = sqrt($v_prev_lensq / 2);
            }
        }

        return new Vector2($v_trans_x / $shrink_by, $v_trans_y / $shrink_by);
    }

    function buildLidFaces()
    {

        $start = count($this->verticesArray) / 3;

        if ($this->bevelEnabled) {
            $layer = 0; // steps + 1
            $offset = $this->vlen * $layer;

            // Bottom faces
            for ($i = 0; $i < $this->flen; $i++) {
                $face = $this->faces[$i];
                $this->f3($face[2] + $offset, $face[1] + $offset, $face[0] + $offset);
            }

            $layer = $this->steps + $this->bevelSegments * 2;
            $offset = $this->vlen * $layer;

            // Top faces
            for ($i = 0; $i < $this->flen; $i++) {
                $face = $this->faces[$i];
                $this->f3($face[0] + $offset, $face[1] + $offset, $face[2] + $offset);
            }
        } else {
            // Bottom faces
            for ($i = 0; $i < $this->flen; $i++) {
                $face = $this->faces[$i];
                $this->f3($face[2], $face[1], $face[0]);
            }

            // Top faces
            for ($i = 0; $i < $this->flen; $i++) {
                $face = $this->faces[$i];
                $this->f3($face[0] + $this->vlen * $this->steps, $face[1] + $this->vlen * $this->steps, $face[2] + $this->vlen * $this->steps);
            }
        }

        $this->addGroup($start, count($this->verticesArray) / 3 - $start, 0);
    }

    function buildSideFaces($contour, $holes)
    {

        $start = count($this->verticesArray) / 3;
        $layeroffset = 0;

        $this->sidewalls($contour, $layeroffset);
        $layeroffset += count($contour);

        foreach ($holes as $ahole) {
            $this->sidewalls($ahole, $layeroffset);
            $layeroffset += count($ahole);
        }

        $this->addGroup($start, count($this->verticesArray) / 3 - $start, 1);
    }

    function sidewalls($contour, $layeroffset)
    {

        $i = count($contour);

        while (--$i >= 0) {
            $j = $i;
            $k = $i - 1;

            if ($k < 0) $k = count($contour) - 1;

            for ($s = 0, $sl = ($this->steps + $this->bevelSegments * 2); $s < $sl; $s++) {
                $slen1 = $this->vlen * $s;
                $slen2 = $this->vlen * ($s + 1);

                $a = $layeroffset + $j + $slen1;
                $b = $layeroffset + $k + $slen1;
                $c = $layeroffset + $k + $slen2;
                $d = $layeroffset + $j + $slen2;

                $this->f4($a, $b, $c, $d);
            }
        }
    }

    function v($x, $y, $z)
    {
        $this->placeholder[] = $x;
        $this->placeholder[] = $y;
        $this->placeholder[] = $z;
    }

    function f3($a, $b, $c)
    {
        $this->addVertex($a);
        $this->addVertex($b);
        $this->addVertex($c);

        $nextIndex = count($this->verticesArray) / 3;
        $uvs = $this->uvgen::generateTopUV($this, $this->verticesArray, $nextIndex - 3, $nextIndex - 2, $nextIndex - 1);

        $this->addUV($uvs[0]);
        $this->addUV($uvs[1]);
        $this->addUV($uvs[2]);
    }

    function f4($a, $b, $c, $d)
    {
        $this->addVertex($a);
        $this->addVertex($b);
        $this->addVertex($d);
        $this->addVertex($b);
        $this->addVertex($c);
        $this->addVertex($d);

        $nextIndex = count($this->verticesArray) / 3;
        $uvs = $this->uvgen::generateSideWallUV($this, $this->verticesArray, $nextIndex - 6, $nextIndex - 3, $nextIndex - 2, $nextIndex - 1);

        $this->addUV($uvs[0]);
        $this->addUV($uvs[1]);
        $this->addUV($uvs[3]);
        $this->addUV($uvs[1]);
        $this->addUV($uvs[2]);
        $this->addUV($uvs[3]);
    }

    function addVertex($index)
    {
        $this->verticesArray[] = $this->placeholder[$index * 3 + 0];
        $this->verticesArray[] = $this->placeholder[$index * 3 + 1];
        $this->verticesArray[] = $this->placeholder[$index * 3 + 2];
    }

    function addUV($vector2)
    {
        $this->uvArray[] = $vector2->x;
        $this->uvArray[] = $vector2->y;
    }

    public function toJSON()
    {
        $data = parent::toJSON();
        $shapes = $this->parameters['shapes'];
        $options = $this->parameters['options'];
        return static::internalToJSON($shapes, $options, $data);
    }

    private static function internalToJSON($shapes, $options, &$data)
    {
        $data['shapes'] = [];

        if (is_array($shapes)) {
            foreach ($shapes as $shape) {
                $data['shapes'][] = $shape->uuid;
            }
        } else {
            $data['shapes'][] = $shapes->uuid;
        }

        $data['options'] = $options;

        if (isset($options['extrudePath'])) {
            $data['options']['extrudePath'] = $options['extrudePath']->toJSON();
        }

        return $data;
    }

    public static function fromJSON($data, $shapes)
    {
        $geometryShapes = [];
        for ($j = 0, $jl = count($data['shapes']); $j < $jl; $j++) {
            $shape = $shapes[$data['shapes'][$j]];
            $geometryShapes[] = $shape;
        }
        $extrudePath = $data['options']['extrudePath'];
        if ($extrudePath !== null) {
            $extrudePathType = $extrudePath['type'];
            $extrudePath = (new $extrudePathType())->fromJSON($extrudePath);
            $data['options']['extrudePath'] = $extrudePath;
        }
        return new ExtrudeGeometry($geometryShapes, $data['options']);
    }
}

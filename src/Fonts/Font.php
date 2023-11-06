<?php

namespace ThreePHP\Fonts;

use ThreePHP\Paths\ShapePath;

class Font
{

    public $isFont;
    public $type;
    public $data;

    public function __construct($data)
    {
        $this->isFont = true;
        $this->type = 'Font';
        $this->data = $data;
    }

    public function generateShapes($text, $size = 100)
    {
        $shapes = [];
        $paths = $this->createPaths($text, $size, $this->data);
        foreach ($paths as $path) {
            $shapes = array_merge($shapes, $path->toShapes());
        }
        return $shapes;
    }

    private function createPaths($text, $size, $data)
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $scale = $size / $data['resolution'];
        $line_height = ($data['boundingBox']['yMax'] - $data['boundingBox']['yMin'] + $data['underlineThickness']) * $scale;
        $paths = [];
        $offsetX = 0;
        $offsetY = 0;

        foreach ($chars as $char) {
            if ($char === "\n") {
                $offsetX = 0;
                $offsetY -= $line_height;
            } else {
                $ret = $this->createPath($char, $scale, $offsetX, $offsetY, $data);
                $offsetX += $ret['offsetX'];
                $paths[] = $ret['path'];
            }
        }

        return $paths;
    }

    private function createPath($char, $scale, $offsetX, $offsetY, $data)
    {
        $glyph = $data['glyphs'][$char] ?? $data['glyphs']['?'];

        if (!$glyph) {
            echo 'THREE.Font: character "' . $char . '" does not exist in font family ' . $data['familyName'] . '.';
            return;
        }

        $path = new ShapePath();
        $x = $y = $cpx = $cpy = $cpx1 = $cpy1 = $cpx2 = $cpy2 = 0;

        if (isset($glyph['o'])) {
            $outline = $glyph['_cachedOutline'] ?? ($glyph['_cachedOutline'] = explode(' ', $glyph['o']));

            for ($i = 0, $l = count($outline); $i < $l;) {
                $action = $outline[$i++];

                switch ($action) {
                    case 'm': // moveTo
                        $x = $outline[$i++] * $scale + $offsetX;
                        $y = $outline[$i++] * $scale + $offsetY;
                        $path->moveTo($x, $y);
                        break;

                    case 'l': // lineTo
                        $x = $outline[$i++] * $scale + $offsetX;
                        $y = $outline[$i++] * $scale + $offsetY;
                        $path->lineTo($x, $y);
                        break;

                    case 'q': // quadraticCurveTo
                        $cpx = $outline[$i++] * $scale + $offsetX;
                        $cpy = $outline[$i++] * $scale + $offsetY;
                        $cpx1 = $outline[$i++] * $scale + $offsetX;
                        $cpy1 = $outline[$i++] * $scale + $offsetY;
                        $path->quadraticCurveTo($cpx1, $cpy1, $cpx, $cpy);
                        break;

                    case 'b': // bezierCurveTo
                        $cpx = $outline[$i++] * $scale + $offsetX;
                        $cpy = $outline[$i++] * $scale + $offsetY;
                        $cpx1 = $outline[$i++] * $scale + $offsetX;
                        $cpy1 = $outline[$i++] * $scale + $offsetY;
                        $cpx2 = $outline[$i++] * $scale + $offsetX;
                        $cpy2 = $outline[$i++] * $scale + $offsetY;
                        $path->bezierCurveTo($cpx1, $cpy1, $cpx2, $cpy2, $cpx, $cpy);
                        break;
                }
            }
        }

        return ['offsetX' => $glyph['ha'] * $scale, 'path' => $path];
    }
}

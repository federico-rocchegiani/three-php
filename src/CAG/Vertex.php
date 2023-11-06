<?php

namespace ThreePHP\CAG;

use ThreePHP\CSG\CSG;

class Vertex
{
    public $pos;
    private $tag;

    public function __construct($pos)
    {
        $this->pos = $pos;
    }

    public function toString()
    {
        return "(" . number_format($this->pos->x, 2) . "," . number_format($this->pos->y, 2) . ")";
    }

    public function getTag()
    {
        $result = $this->tag;
        if (!$result) {
            $result = CSG::getTag();
            $this->tag = $result;
        }
        return $result;
    }
}

<?php

namespace ThreePHP\Paths;

class Node
{
    public $i;
    public $x;
    public $y;
    public $prev;
    public $next;
    public $z;
    public $prevZ;
    public $nextZ;
    public $steiner;

    public function __construct($i, $x, $y)
    {
        $this->i = $i;
        $this->x = $x;
        $this->y = $y;
        $this->prev = null;
        $this->next = null;
        $this->z = 0;
        $this->prevZ = null;
        $this->nextZ = null;
        $this->steiner = false;
    }
}

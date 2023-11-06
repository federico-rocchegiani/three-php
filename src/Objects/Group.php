<?php

namespace ThreePHP\Objects;

use ThreePHP\Core\Object3D;

class Group extends Object3D
{
    public readonly bool $isGroup;

    public function __construct()
    {
        parent::__construct();
        $this->isGroup = true;
        $this->type = 'Group';
    }
}

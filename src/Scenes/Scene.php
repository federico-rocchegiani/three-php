<?php

namespace ThreePHP\Scenes;

use ThreePHP\Core\Object3D;

class Scene extends Object3D
{
    public readonly bool $isScene;

    public $background = null;
    public $environment = null;
    public $fog = null;
    public $backgroundBlurriness = 0;
    public $backgroundIntensity = 1;
    public $overrideMaterial = null;

    public function __construct()
    {
        parent::__construct();

        $this->type = 'Scene';
        $this->isScene = true;
    }
}

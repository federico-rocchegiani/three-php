<?php

namespace ThreePHP\Paths;

use ThreePHP\Math\MathUtils;

class Shape extends Path
{
    public $uuid;
    public $holes;

    public function __construct($points = [])
    {
        parent::__construct($points);
        $this->uuid = MathUtils::uuid();
        $this->type = 'Shape';
        $this->holes = [];
    }

    public function getPointsHoles($divisions)
    {
        $holesPts = [];
        for ($i = 0, $l = count($this->holes); $i < $l; $i++) {
            $holesPts[$i] = $this->holes[$i]->getPoints($divisions);
        }
        return $holesPts;
    }

    // get points of shape and holes (keypoints based on segments parameter)
    public function extractPoints($divisions)
    {
        return [
            'shape' => $this->getPoints($divisions),
            'holes' => $this->getPointsHoles($divisions),
        ];
    }

    public function copy($source)
    {
        parent::copy($source);
        $this->holes = [];
        for ($i = 0, $l = count($source->holes); $i < $l; $i++) {
            $hole = $source->holes[$i];
            $this->holes[] = $hole->clone();
        }
        return $this;
    }

    public function toJSON()
    {
        $data = parent::toJSON();
        $data['uuid'] = $this->uuid;
        $data['holes'] = [];
        for ($i = 0, $l = count($this->holes); $i < $l; $i++) {
            $hole = $this->holes[$i];
            $data['holes'][] = $hole->toJSON();
        }
        return $data;
    }

    public function fromJSON($json)
    {
        parent::fromJSON($json);
        $this->uuid = $json['uuid'];
        $this->holes = [];
        for ($i = 0, $l = count($json['holes']); $i < $l; $i++) {
            $hole = $json['holes'][$i];
            $this->holes[] = (new Path())->fromJSON($hole);
        }
        return $this;
    }
}

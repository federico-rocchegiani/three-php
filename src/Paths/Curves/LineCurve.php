<?php

namespace ThreePHP\Paths\Curves;

use ThreePHP\Math\Vector2;
use ThreePHP\Paths\Curve;

class LineCurve extends Curve
{

    public $isLineCurve;
    public $v1;
    public $v2;

    public function __construct($v1 = null, $v2 = null)
    {
        parent::__construct();
        $this->isLineCurve = true;
        $this->type = 'LineCurve';
        $this->v1 = $v1 ?: new Vector2();
        $this->v2 = $v2 ?: new Vector2();
    }

    public function getPoint($t, $optionalTarget = null)
    {
        $point = $optionalTarget ?: new Vector2();
        if ($t === 1) {
            $point->copy($this->v2);
        } else {
            $point->copy($this->v2)->sub($this->v1);
            $point->multiplyScalar($t)->add($this->v1);
        }
        return $point;
    }

    public function getPointAt($u, $optionalTarget = null)
    {
        return $this->getPoint($u, $optionalTarget);
    }

    public function getTangent($t, $optionalTarget = null)
    {
        $tangent = $optionalTarget ?: new Vector2();
        $tangent->copy($this->v2)->sub($this->v1)->normalize();
        return $tangent;
    }

    public function copy($source)
    {
        parent::copy($source);
        $this->v1->copy($source->v1);
        $this->v2->copy($source->v2);
        return $this;
    }

    public function toJSON()
    {
        $data = parent::toJSON();
        $data['v1'] = $this->v1->toArray();
        $data['v2'] = $this->v2->toArray();
        return $data;
    }

    public function fromJSON($json)
    {
        parent::fromJSON($json);
        $this->v1->fromArray($json['v1']);
        $this->v2->fromArray($json['v2']);
        return $this;
    }
}

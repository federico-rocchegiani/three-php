<?php

namespace ThreePHP\Core;

class Layers
{
    private $mask = 1;

    public function __construct()
    {
        $this->mask = 1;
    }

    public function set($channel)
    {
        $this->mask = (1 << $channel) & 0xFFFFFFFF;
    }

    public function enable($channel)
    {
        $this->mask |= (1 << $channel) & 0xFFFFFFFF;
    }

    public function enableAll()
    {
        $this->mask = 0xFFFFFFFF;
    }

    public function toggle($channel)
    {
        $this->mask ^= (1 << $channel) & 0xFFFFFFFF;
    }

    public function disable($channel)
    {
        $this->mask &= ~((1 << $channel) & 0xFFFFFFFF);
    }

    public function disableAll()
    {
        $this->mask = 0;
    }

    public function test($layers)
    {
        return ($this->mask & $layers->mask) !== 0;
    }

    public function isEnabled($channel)
    {
        return ($this->mask & (1 << $channel) & 0xFFFFFFFF) !== 0;
    }
}

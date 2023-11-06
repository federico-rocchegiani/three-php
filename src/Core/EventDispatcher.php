<?php

namespace ThreePHP\Core;

use ThreePHP\Events\Event;

class EventDispatcher
{

    protected $listeners = [];

    public function addEventListener($type, $listener)
    {
    }

    public function hasEventListener($type, $listener)
    {
    }

    public function removeEventListener($type, $listener)
    {
    }

    public function dispatchEvent(Event $event)
    {
    }
}

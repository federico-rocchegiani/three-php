<?php

namespace ThreePHP\Core;

trait ChangeHandler
{
    /**
     * @var callable
     */
    private $change_callback;

    public function __get($property): mixed
    {
        if (property_exists($this, $property)) {
            return $this->{$property};
        }
        return null;
    }

    public function __set($property, $value): void
    {
        if (property_exists($this, $property)) {
            $this->{$property} = $value;
            $this->onChangeCallback();
        }
    }

    public function setChangeCallback(callable $change_callback): void
    {
        $this->change_callback = $change_callback;
    }

    private function onChangeCallback(): void
    {
        if (!is_callable($this->change_callback)) {
            return;
        }
        call_user_func($this->change_callback, []);
    }
}

<?php

namespace Nucleus\Container;

class Container implements ContainerInterface
{

    protected array $injections = [];

    #[\Override] public function get($id)
    {
        if (isset($this->injections[$id])){
            return $this->injections[$id];
        }
        return null;
    }

    #[\Override] public function has($id)
    {
        return isset($this->injections[$id]);
    }

    public function set($id, $value)
    {
        $this->injections[$id] = $value;
    }
}
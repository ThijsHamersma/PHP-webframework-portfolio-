<?php
namespace Psr\Http\Message;

class Uri {
    public string $path;

    public function __construct($path){
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}

<?php

namespace NeoCube\Render;

use NeoCube\Render\RenderInterface;
use NeoCube\Response;

class ArrayJson implements RenderInterface {

    public function __construct(private array $data){ }

    public function render() : void {
        Response::json($this->data,200,false);
    }
}

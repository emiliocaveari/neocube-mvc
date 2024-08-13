<?php

namespace NeoCube\View;

use NeoCube\View\ViewRenderInterface;
use NeoCube\Response;

class RenderJson implements ViewRenderInterface {

    public function __construct(private array $data) {
    }

    public function render(): void {
        Response::json($this->data, 200, false);
    }
}

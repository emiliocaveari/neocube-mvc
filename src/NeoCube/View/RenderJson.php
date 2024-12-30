<?php

namespace NeoCube\View;

use NeoCube\View\ViewRenderInterface;
use NeoCube\Response;

class RenderJson implements ViewRenderInterface {

    public function __construct(
        private mixed $data,
        private int $status = 200
    ) {
    }

    public function render(): void {
        Response::json($this->data, $this->status, false);
    }
}

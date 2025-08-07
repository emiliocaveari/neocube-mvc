<?php

namespace NeoCube\View;

use NeoCube\Response;

class RenderHtml implements ViewRenderInterface {

    public function __construct(private string $html) {
    }

    public function render(): void {
        Response::html($this->html)->execute();
    }
}

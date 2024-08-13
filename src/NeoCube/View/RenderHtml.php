<?php

namespace NeoCube\View;

use NeoCube\Response;
use NeoCube\View;

class RenderHtml implements ViewRenderInterface {

    public function __construct(private View $view) {
    }

    public function render(): void {
        $html = $this->view->renderAll();
        Response::html($html);
    }
}

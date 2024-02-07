<?php

namespace NeoCube\Render;

use NeoCube\Render\RenderInterface;
use NeoCube\Response;
use NeoCube\View;

class ViewHtml implements RenderInterface {

    public function __construct(private View $view){ }

    public function render() : void {
        $html = $this->view->renderAll();
        Response::html($html);
    }
}

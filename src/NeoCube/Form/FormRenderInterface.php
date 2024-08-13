<?php

namespace NeoCube\Form;

interface FormRenderInterface {

    static public function render(array | ElementAbstract $elements): string;
}

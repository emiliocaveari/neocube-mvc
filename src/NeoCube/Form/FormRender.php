<?php

namespace NeoCube\Form;

use NeoCube\Form\RenderInterface;

class FormRender implements RenderInterface {

    static public function render(array | ElementAbstract $elements): string {

        $htmlRender = '';

        if (!is_array($elements))
            $elements = array($elements);

        foreach ($elements as $element) {
            switch ($element->type()) {
                case 'submit':
                case 'button':
                case 'reset':
                case 'hidden':
                    $htmlRender .= $element->input();
                    break;
                case 'radio':
                case 'checkbox':
                    $htmlRender .=
                        '<label ' . $element->attrLabel() . ' >' .
                        $element->input() .
                        $element->label() .
                        '</label>';
                    break;
                default:
                    $htmlRender .=
                        '<label ' . $element->attrLabel() . ' >' .
                        $element->label() .
                        $element->input() .
                        '</label>';
                    break;
            }
        }

        return $htmlRender;
    }
}

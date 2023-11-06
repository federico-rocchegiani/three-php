<?php

namespace ThreePHP\Geometries;

class TextGeometry extends ExtrudeGeometry
{

    public function __construct($text, $parameters = [])
    {
        $font = $parameters['font'] ?? null;

        if ($font === null) {
            parent::__construct(); // Genera la geometria di estrusione predefinita
        } else {
            $shapes = $font->generateShapes($text, $parameters['size']);

            // Traduci i parametri all'API di ExtrudeGeometry
            $parameters['depth'] = isset($parameters['height']) ? $parameters['height'] : 50;

            // Valori predefiniti
            if (!isset($parameters['bevelThickness'])) {
                $parameters['bevelThickness'] = 10;
            }
            if (!isset($parameters['bevelSize'])) {
                $parameters['bevelSize'] = 8;
            }
            if (!isset($parameters['bevelEnabled'])) {
                $parameters['bevelEnabled'] = false;
            }

            parent::__construct($shapes, $parameters);
        }

        $this->type = 'TextGeometry';
    }
}

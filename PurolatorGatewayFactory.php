<?php

namespace Omnibus\Purolator;

use Omnibus\Core\GatewayFactory;

class PurolatorGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'omnibus.factory_name' => 'purolator',
            'omnibus.factory_title' => 'Purolator'
        ]);
    }
}
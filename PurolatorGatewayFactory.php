<?php

namespace Omnibus\Purolator;

use Omnibus\Config;
use Omnibus\Exception\InvalidConfigException;
use Omnibus\GatewayFactory;
use Omnibus\Purolator\Action\CancelAction;
use Omnibus\Purolator\Action\PickupAction;
use Omnibus\Purolator\Action\RatingAction;
use Omnibus\Purolator\Action\ShippingAction;
use Omnibus\Purolator\Action\TrackingAction;
use Symfony\Component\HttpClient\HttpClient;

/**
 *   options:
 *     key: '%env(PUROLATOR_KEY)%'              # the development or production key (eship.purolator.com)
 *     password: '%env(PUROLATOR_PASSWORD)%'    # its password
 *     account_number: '%env(PUROLATOR_ACCOUNT)%'
 *     sandbox: true
 *     rates: [...]                             # optional: configured prices instead of Estimating
 */
final class PurolatorGatewayFactory extends GatewayFactory
{
    protected function populateConfig(Config $config): void
    {
        $config->defaults([
            'omnibus.factory_name' => 'purolator',
            'omnibus.factory_title' => 'Purolator',
            'omnibus.required_options' => ['key', 'password', 'account_number'],
            'sandbox' => false,
            'omnibus.api' => function (Config $c) {
                $http = $this->http ?? (class_exists(HttpClient::class) ? HttpClient::create() : throw new InvalidConfigException('The "purolator" gateway needs symfony/http-client.'));

                return new Api($http, (string) $c['key'], (string) $c['password'], (string) $c['account_number'], (bool) $c['sandbox']);
            },
            'omnibus.action.rating' => static fn (Config $c) => $c->get('rates') ? null : new RatingAction(),
            'omnibus.action.shipping' => new ShippingAction(),
            'omnibus.action.tracking' => new TrackingAction(),
            'omnibus.action.pickup' => new PickupAction(),
            'omnibus.action.cancel' => new CancelAction(),
        ]);
    }
}

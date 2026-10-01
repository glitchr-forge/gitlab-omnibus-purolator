<?php

namespace Omnibus\Purolator\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Purolator\Api;
use Omnibus\Request\Cancel;
use Omnibus\Request\Request;

/** Shipping's VoidShipment: a shipment not yet picked up, cancelled. */
final class CancelAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Cancel;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Cancel);
        $data = $this->api->call(Api::SHIPPING, 'VoidShipmentRequest', '<v2:PIN><v2:Value>'.Api::e($request->trackingNumber).'</v2:Value></v2:PIN>');
        $request->setResult('true' === strtolower((string) ($data->ShipmentVoided ?? 'false')));
    }
}

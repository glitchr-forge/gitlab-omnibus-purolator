<?php

namespace Omnibus\Purolator\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Model\Rate;
use Omnibus\Purolator\Api;
use Omnibus\Purolator\Mapping;
use Omnibus\Request\Rating;
use Omnibus\Request\Request;

/** Estimating's GetFullEstimate: every service for the shipment, its total and transit days. */
final class RatingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Rating;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Rating);
        $data = $this->api->call(Api::ESTIMATING, 'GetFullEstimateRequest', Mapping::shipment($request->shipment, $this->api->accountNumber).'<v2:ShowAlternativeServicesIndicator>true</v2:ShowAlternativeServicesIndicator>');
        $rates = [];
        foreach ($data->ShipmentEstimates->ShipmentEstimate ?? [] as $estimate) {
            $code = (string) $estimate->ServiceID;
            $rates[] = new Rate('purolator', $code, Mapping::SERVICES[$code] ?? $code, (int) round(((float) $estimate->TotalPrice) * 100), 'CAD', isset($estimate->EstimatedTransitDays) ? (int) $estimate->EstimatedTransitDays : null);
        }
        usort($rates, static fn (Rate $a, Rate $b) => $a->amount <=> $b->amount);
        $request->setResult($rates);
    }
}

<?php

namespace Omnibus\Purolator\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Model\Address;
use Omnibus\Model\PickupPoint;
use Omnibus\Purolator\Api;
use Omnibus\Request\Pickup;
use Omnibus\Request\Request;

/** Locator's GetLocationsByPostalCode: shipping centres and drop boxes near a postal code. */
final class PickupAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Pickup;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Pickup);
        $near = $request->near;
        $data = $this->api->call(Api::LOCATOR, 'GetLocationsByPostalCodeRequest', '<v2:PostalCode>'.Api::e(strtoupper(str_replace(' ', '', $near->postcode))).'</v2:PostalCode><v2:Count>'.min(50, max(1, $request->limit)).'</v2:Count><v2:LocationTypes><v2:LocationType>ShippingCentre</v2:LocationType><v2:LocationType>DropBox</v2:LocationType><v2:LocationType>Agent</v2:LocationType></v2:LocationTypes><v2:MaxDistance>25</v2:MaxDistance><v2:Units>km</v2:Units>');
        $points = [];
        foreach ($data->Locations->Location ?? [] as $location) {
            $a = $location->Address;
            $hours = [];
            foreach ($location->HoursOfOperation->Hours ?? [] as $h) {
                $n = array_search((string) $h->DayOfWeek, ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], true);
                if ($n) {
                    $hours[$n][] = [(string) $h->OpenTime, (string) $h->CloseTime];
                }
            }
            $points[] = new PickupPoint('purolator', (string) $location->ID, (string) ($location->Name ?: $location->LocationType),
                new Address((string) $location->Name, array_values(array_filter([trim((string) $a->StreetNumber.' '.(string) $a->StreetName)])), (string) $a->PostalCode, (string) $a->City, (string) ($a->Country ?: 'CA')),
                isset($location->Latitude) ? (float) $location->Latitude : null, isset($location->Longitude) ? (float) $location->Longitude : null,
                $hours, isset($location->Distance) ? (int) round(((float) $location->Distance) * 1000) : null);
        }
        $request->setResult(\array_slice($points, 0, $request->limit));
    }
}

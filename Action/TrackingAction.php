<?php

namespace Omnibus\Purolator\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Model\Tracking as TrackingModel;
use Omnibus\Model\TrackingEvent;
use Omnibus\Model\TrackingStatus;
use Omnibus\Purolator\Api;
use Omnibus\Purolator\Mapping;
use Omnibus\Request\Request;
use Omnibus\Request\Tracking;

/** Tracking's TrackPackagesByPin: the scans, oldest first. */
final class TrackingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Tracking;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Tracking);
        $data = $this->api->call(Api::TRACKING, 'TrackPackagesByPinRequest', '<v2:PINs><v2:PIN><v2:Value>'.Api::e($request->trackingNumber).'</v2:Value></v2:PIN></v2:PINs>', str_starts_with($request->locale, 'fr') ? 'fr' : 'en');
        $events = [];
        foreach ($data->TrackingInformationList->TrackingInformation[0]->Scans->Scan ?? [] as $scan) {
            $events[] = new TrackingEvent(new \DateTimeImmutable((string) $scan->ScanDate.' '.(string) $scan->ScanTime), Mapping::status((string) $scan->ScanType, (string) $scan->Description), (string) $scan->Description, trim((string) ($scan->Depot->Name ?? '')) ?: null, (string) $scan->ScanType ?: null);
        }
        usort($events, static fn (TrackingEvent $a, TrackingEvent $b) => $a->at <=> $b->at);
        $request->setResult(new TrackingModel('purolator', $request->trackingNumber, $events ? $events[array_key_last($events)]->status : TrackingStatus::UNKNOWN, $events));
    }
}

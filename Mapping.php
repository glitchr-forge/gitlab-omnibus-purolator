<?php

namespace Omnibus\Purolator;

use Omnibus\Model\Address;
use Omnibus\Model\Parcel;
use Omnibus\Model\Shipment;
use Omnibus\Model\TrackingStatus;

/** Purolator's XML for ours. */
final class Mapping
{
    public const SERVICES = ['PurolatorExpress' => 'Purolator Express', 'PurolatorExpress9AM' => 'Purolator Express 9AM', 'PurolatorExpress10:30AM' => 'Purolator Express 10:30AM', 'PurolatorExpress12PM' => 'Purolator Express 12PM', 'PurolatorGround' => 'Purolator Ground', 'PurolatorExpressU.S.' => 'Purolator Express U.S.', 'PurolatorGroundU.S.' => 'Purolator Ground U.S.', 'PurolatorExpressInternational' => 'Purolator Express International'];

    public static function address(Address $a, ?string $province): string
    {
        $names = explode(' ', trim($a->name), 2);
        $phone = preg_replace('/\D+/', '', (string) $a->phone) ?: '0000000000';
        $street = preg_match('/^(\d+[A-Za-z]?)\s+(.+)$/', $a->line(0), $m) ? [$m[1], $m[2]] : ['', $a->line(0)];

        return '<v2:Name>'.Api::e(mb_substr($a->name, 0, 30)).'</v2:Name>'
            .($a->company ? '<v2:Company>'.Api::e(mb_substr($a->company, 0, 30)).'</v2:Company>' : '')
            .'<v2:StreetNumber>'.Api::e($street[0]).'</v2:StreetNumber><v2:StreetName>'.Api::e(mb_substr($street[1], 0, 30)).'</v2:StreetName>'
            .('' !== $a->line(1) ? '<v2:StreetAddress2>'.Api::e(mb_substr($a->line(1), 0, 30)).'</v2:StreetAddress2>' : '')
            .'<v2:City>'.Api::e($a->city).'</v2:City><v2:Province>'.Api::e((string) $province).'</v2:Province><v2:Country>'.strtoupper($a->country).'</v2:Country>'
            .'<v2:PostalCode>'.Api::e(strtoupper(str_replace(' ', '', $a->postcode))).'</v2:PostalCode>'
            .'<v2:PhoneNumber><v2:CountryCode>'.('US' === strtoupper($a->country) || 'CA' === strtoupper($a->country) ? '1' : '0').'</v2:CountryCode><v2:AreaCode>'.substr($phone, -10, 3).'</v2:AreaCode><v2:Phone>'.substr($phone, -7).'</v2:Phone></v2:PhoneNumber>';
    }

    public static function piece(Parcel $p): string
    {
        return '<v2:Piece><v2:Weight><v2:Value>'.number_format(max(0.1, $p->weight / 1000), 1, '.', '').'</v2:Value><v2:WeightUnit>kg</v2:WeightUnit></v2:Weight>'
            .($p->length && $p->width && $p->height ? '<v2:Length><v2:Value>'.$p->length.'</v2:Value><v2:DimensionUnit>cm</v2:DimensionUnit></v2:Length><v2:Width><v2:Value>'.$p->width.'</v2:Value><v2:DimensionUnit>cm</v2:DimensionUnit></v2:Width><v2:Height><v2:Value>'.$p->height.'</v2:Value><v2:DimensionUnit>cm</v2:DimensionUnit></v2:Height>' : '')
            .'</v2:Piece>';
    }

    /** The <Shipment> both Estimating and Shipping take. */
    public static function shipment(Shipment $s, string $account): string
    {
        $international = 'CA' !== strtoupper($s->recipient->country);

        return '<v2:Shipment><v2:SenderInformation><v2:Address>'.self::address($s->sender, $s->option('sender_province')).'</v2:Address></v2:SenderInformation>'
            .'<v2:ReceiverInformation><v2:Address>'.self::address($s->recipient, $s->option('recipient_province')).'</v2:Address></v2:ReceiverInformation>'
            .'<v2:ShipmentDate>'.($s->shippingDate ?? new \DateTimeImmutable())->format('Y-m-d').'</v2:ShipmentDate>'
            .'<v2:PackageInformation><v2:ServiceID>'.Api::e($s->service ?? ($international ? 'PurolatorExpressInternational' : 'PurolatorExpress')).'</v2:ServiceID>'
            .'<v2:TotalWeight><v2:Value>'.number_format(max(0.1, $s->weight() / 1000), 1, '.', '').'</v2:Value><v2:WeightUnit>kg</v2:WeightUnit></v2:TotalWeight><v2:TotalPieces>'.\count($s->parcels).'</v2:TotalPieces>'
            .'<v2:PiecesInformation>'.implode('', array_map([self::class, 'piece'], $s->parcels)).'</v2:PiecesInformation></v2:PackageInformation>'
            .($international ? '<v2:InternationalInformation><v2:DocumentsOnlyIndicator>false</v2:DocumentsOnlyIndicator><v2:ContentDetails><v2:ContentDetail><v2:Description>'.Api::e((string) $s->option('description', 'Merchandise')).'</v2:Description><v2:CountryOfManufacture>'.strtoupper($s->sender->country).'</v2:CountryOfManufacture><v2:UnitValue>'.number_format(($s->parcels[0]->value ?? 100) / 100, 2, '.', '').'</v2:UnitValue><v2:Quantity>1</v2:Quantity></v2:ContentDetail></v2:ContentDetails><v2:BuyerInformation><v2:Address>'.self::address($s->recipient, $s->option('recipient_province')).'</v2:Address></v2:BuyerInformation><v2:PreferredCustomsBroker/><v2:DutyInformation><v2:BillDutiesToParty>Receiver</v2:BillDutiesToParty><v2:BusinessRelationship>NotRelated</v2:BusinessRelationship><v2:Currency>'.Api::e($s->parcels[0]->currency).'</v2:Currency></v2:DutyInformation><v2:ImportExportType>Permanent</v2:ImportExportType></v2:InternationalInformation>' : '')
            .'<v2:PaymentInformation><v2:PaymentType>Sender</v2:PaymentType><v2:RegisteredAccountNumber>'.Api::e($account).'</v2:RegisteredAccountNumber><v2:BillingAccountNumber>'.Api::e($account).'</v2:BillingAccountNumber></v2:PaymentInformation>'
            .'<v2:PickupInformation><v2:PickupType>'.Api::e((string) $s->option('pickup_type', 'DropOff')).'</v2:PickupType></v2:PickupInformation>'
            .($s->reference ? '<v2:TrackingReferenceInformation><v2:Reference1>'.Api::e(mb_substr($s->reference, 0, 30)).'</v2:Reference1></v2:TrackingReferenceInformation>' : '')
            .'</v2:Shipment>';
    }

    public static function status(?string $scanType, ?string $description = null): TrackingStatus
    {
        $d = strtolower((string) $description);

        return match (strtoupper((string) $scanType)) {
            'DELIVERY', 'DELIVERED' => TrackingStatus::DELIVERED,
            'ONDELIVERY', 'OUTFORDELIVERY' => TrackingStatus::OUT_FOR_DELIVERY,
            'PICKUP', 'DEPOT', 'PROOFOFPICKUP', 'INTRANSIT', 'OTHER' => str_contains($d, 'available for pickup') ? TrackingStatus::AVAILABLE_FOR_PICKUP : TrackingStatus::IN_TRANSIT,
            'UNDELIVERABLE', 'EXCEPTION', 'UNDELIVERED' => TrackingStatus::EXCEPTION,
            'RETURNED', 'RETURNTOSENDER' => TrackingStatus::RETURNED,
            'SHIPMENTCREATED', 'SHIPPING' => TrackingStatus::PENDING,
            default => TrackingStatus::UNKNOWN,
        };
    }
}

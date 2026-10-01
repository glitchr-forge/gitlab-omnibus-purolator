# omnibus/purolator

Purolator for [glitchr/omnibus](https://github.com/glitchr-studio/omnibus): estimates, shipments
with their documents, tracking, locations and voids - the E-Ship web services (SOAP 1.2, basic
auth with the API key).

```yaml
omnibus:
    gateways:
        purolator:
            factory: purolator
            options:
                key: '%env(PUROLATOR_KEY)%'            # development or production key
                password: '%env(PUROLATOR_PASSWORD)%'
                account_number: '%env(PUROLATOR_ACCOUNT)%'
                sandbox: true
                rates: [...]                           # optional: configured prices instead of Estimating
```

The service is Purolator's ServiceID (PurolatorExpress, PurolatorGround, PurolatorExpress9AM,
PurolatorExpressU.S., PurolatorExpressInternational...). Shipment options: `sender_province` and
`recipient_province` (two letters), `pickup_type` (DropOff, PreScheduled), `label_format` (PDF,
ZPL for thermal), `description` (customs). Purolator wants the street number apart from the
street name: the first line is split on its leading number.

Credentials: an [E-Ship Web Services](https://eship.purolator.com) registration gives a
development key and password; production keys follow certification; plus your account number.

Built from Purolator's published API documentation and tested on recorded answers; not yet run
against the development environment: that needs the credentials above.

License: LGPL-3.0-or-later.

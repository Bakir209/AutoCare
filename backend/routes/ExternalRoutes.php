<?php
// External API (NHTSA) endpoints. The frontend calls these, never NHTSA directly.
Flight::route('GET /vin/@vin', function ($vin) {
    respond(Flight::nhtsaService()->decodeVin($vin));
});

Flight::route('GET /vehicles/@id:[0-9]+/recalls', function ($id) {
    respond(Flight::nhtsaService()->recalls((int)$id));
});

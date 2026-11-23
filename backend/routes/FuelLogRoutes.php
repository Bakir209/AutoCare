<?php
// Fuel log endpoints. Routes only translate HTTP to service calls and back.
Flight::route('GET /fuel-logs', function () {
    respond(Flight::fuelLogService()->getAll());
});

Flight::route('GET /fuel-logs/@id:[0-9]+', function ($id) {
    respond(Flight::fuelLogService()->getById((int)$id));
});

Flight::route('POST /fuel-logs', function () {
    respond(Flight::fuelLogService()->create(jsonBody()), 201);
});

Flight::route('PUT /fuel-logs/@id:[0-9]+', function ($id) {
    respond(Flight::fuelLogService()->update((int)$id, jsonBody()));
});

Flight::route('DELETE /fuel-logs/@id:[0-9]+', function ($id) {
    Flight::fuelLogService()->delete((int)$id);
    respond(['message' => 'Fuel log deleted.']);
});

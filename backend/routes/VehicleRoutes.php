<?php
// Vehicle endpoints. Routes only translate HTTP to service calls and back.
Flight::route('GET /vehicles', function () {
    respond(Flight::vehicleService()->getAll());
});

Flight::route('GET /vehicles/@id:[0-9]+', function ($id) {
    respond(Flight::vehicleService()->getById((int)$id));
});

Flight::route('POST /vehicles', function () {
    respond(Flight::vehicleService()->create(jsonBody()), 201);
});

Flight::route('PUT /vehicles/@id:[0-9]+', function ($id) {
    respond(Flight::vehicleService()->update((int)$id, jsonBody()));
});

Flight::route('DELETE /vehicles/@id:[0-9]+', function ($id) {
    Flight::vehicleService()->delete((int)$id);
    respond(['message' => 'Vehicle deleted.']);
});

// Records that belong to one vehicle (used by the vehicle details tabs).
Flight::route('GET /vehicles/@id:[0-9]+/service-records', function ($id) {
    respond(Flight::serviceRecordService()->getByVehicle((int)$id));
});

Flight::route('GET /vehicles/@id:[0-9]+/fuel-logs', function ($id) {
    respond(Flight::fuelLogService()->getByVehicle((int)$id));
});

Flight::route('GET /vehicles/@id:[0-9]+/reminders', function ($id) {
    respond(Flight::reminderService()->getByVehicle((int)$id));
});

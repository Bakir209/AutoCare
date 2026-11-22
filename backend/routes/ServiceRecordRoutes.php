<?php
// Service record endpoints. Routes only translate HTTP to service calls and back.
Flight::route('GET /service-records', function () {
    respond(Flight::serviceRecordService()->getAll());
});

Flight::route('GET /service-records/@id:[0-9]+', function ($id) {
    respond(Flight::serviceRecordService()->getById((int)$id));
});

Flight::route('POST /service-records', function () {
    respond(Flight::serviceRecordService()->create(jsonBody()), 201);
});

Flight::route('PUT /service-records/@id:[0-9]+', function ($id) {
    respond(Flight::serviceRecordService()->update((int)$id, jsonBody()));
});

Flight::route('DELETE /service-records/@id:[0-9]+', function ($id) {
    Flight::serviceRecordService()->delete((int)$id);
    respond(['message' => 'Service record deleted.']);
});

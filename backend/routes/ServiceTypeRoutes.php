<?php
// Service type endpoints. Routes only translate HTTP to service calls and back.
Flight::route('GET /service-types', function () {
    respond(Flight::serviceTypeService()->getAll());
});

Flight::route('GET /service-types/@id:[0-9]+', function ($id) {
    respond(Flight::serviceTypeService()->getById((int)$id));
});

Flight::route('POST /service-types', function () {
    respond(Flight::serviceTypeService()->create(jsonBody()), 201);
});

Flight::route('PUT /service-types/@id:[0-9]+', function ($id) {
    respond(Flight::serviceTypeService()->update((int)$id, jsonBody()));
});

Flight::route('DELETE /service-types/@id:[0-9]+', function ($id) {
    Flight::serviceTypeService()->delete((int)$id);
    respond(['message' => 'Service type deleted.']);
});

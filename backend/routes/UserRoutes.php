<?php
// User endpoints. Routes only translate HTTP to service calls and back.
Flight::route('GET /users', function () {
    respond(Flight::userService()->getAll());
});

Flight::route('GET /users/@id:[0-9]+', function ($id) {
    respond(Flight::userService()->getById((int)$id));
});

Flight::route('POST /users', function () {
    respond(Flight::userService()->create(jsonBody()), 201);
});

Flight::route('PUT /users/@id:[0-9]+', function ($id) {
    respond(Flight::userService()->update((int)$id, jsonBody()));
});

Flight::route('DELETE /users/@id:[0-9]+', function ($id) {
    Flight::userService()->delete((int)$id);
    respond(['message' => 'User deleted.']);
});

Flight::route('GET /users/@id:[0-9]+/vehicles', function ($id) {
    respond(Flight::vehicleService()->getByUser((int)$id));
});

<?php
// Reminder endpoints. Routes only translate HTTP to service calls and back.
Flight::route('GET /reminders', function () {
    respond(Flight::reminderService()->getAll());
});

Flight::route('GET /reminders/@id:[0-9]+', function ($id) {
    respond(Flight::reminderService()->getById((int)$id));
});

Flight::route('POST /reminders', function () {
    respond(Flight::reminderService()->create(jsonBody()), 201);
});

Flight::route('PUT /reminders/@id:[0-9]+', function ($id) {
    respond(Flight::reminderService()->update((int)$id, jsonBody()));
});

Flight::route('DELETE /reminders/@id:[0-9]+', function ($id) {
    Flight::reminderService()->delete((int)$id);
    respond(['message' => 'Reminder deleted.']);
});

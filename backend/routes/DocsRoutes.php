<?php
// API home, Swagger UI page and the OpenAPI document it reads.
Flight::route('GET /', function () {
    respond(['name' => 'AutoCare API', 'documentation' => 'docs']);
});

Flight::route('GET /docs', function () {
    Flight::response()
        ->header('Content-Type', 'text/html; charset=utf-8')
        ->write(file_get_contents(__DIR__ . '/../public/swagger.html'));
});

Flight::route('GET /openapi.json', function () {
    Flight::response()
        ->header('Content-Type', 'application/json; charset=utf-8')
        ->write(file_get_contents(__DIR__ . '/../public/openapi.json'));
});

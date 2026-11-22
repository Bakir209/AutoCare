<?php
/**
 * AutoCare REST API entry point (FlightPHP).
 * Local run:  php -S localhost:8001 -t backend backend/index.php
 */
require __DIR__ . '/vendor/autoload.php';

// Load our own classes from these folders on demand.
spl_autoload_register(function (string $class) {
    foreach (['config', 'dao', 'services', 'middleware'] as $dir) {
        $file = __DIR__ . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require __DIR__ . '/routes/helpers.php';

// Services are created the first time a route asks for them: Flight::userService()
Flight::register('userService', 'UserService');
Flight::register('vehicleService', 'VehicleService');
Flight::register('serviceTypeService', 'ServiceTypeService');
Flight::register('serviceRecordService', 'ServiceRecordService');
Flight::register('fuelLogService', 'FuelLogService');
Flight::register('reminderService', 'ReminderService');
Flight::register('nhtsaService', 'NhtsaService');

// Any exception thrown by a route or service ends up here and becomes JSON.
Flight::map('error', function (Throwable $e) {
    $errors = [];

    if ($e instanceof ApiException) {
        $status = $e->getStatus();
        $message = $e->getMessage();
        $errors = $e->getErrors();
    } elseif ($e instanceof PDOException && $e->getCode() === '23000') {
        $status = 409;                                   // foreign key / unique violation
        $message = 'This change conflicts with existing data.';
        Logger::error('Database constraint error: ' . $e->getMessage());
    } else {
        $status = 500;                                   // never leak details to the client
        $message = 'Something went wrong on the server.';
        Logger::error(get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    }

    $body = ['error' => $message];
    if ($errors) {
        $body['errors'] = $errors;
    }
    Flight::response()->clearBody();
    respond($body, $status);
    Flight::response()->send();   // errors skip the normal send, so send explicitly
});

Flight::map('notFound', function () {
    respond(['error' => 'Route not found.'], 404);
});

// CORS: lets the frontend (another origin during development) call the API.
Flight::before('start', function () {
    header('Access-Control-Allow-Origin: ' . Config::get('cors_origin', '*'));
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    if (Flight::request()->method === 'OPTIONS') {
        Flight::halt(204);
    }
});

foreach (glob(__DIR__ . '/routes/*Routes.php') as $routeFile) {
    require $routeFile;
}

Flight::start();

<?php
/** Send a JSON response (keeps accents like Š readable). */
function respond(mixed $data, int $status = 200): void
{
    Flight::json($data, $status, true, 'utf-8', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/** Decode the JSON request body into an array, or fail with 400. */
function jsonBody(): array
{
    $raw = Flight::request()->getBody();
    if ($raw === '' || $raw === null) {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new ApiException(400, 'Request body must be valid JSON.');
    }
    return $data;
}

<?php
/**
 * External API: US NHTSA (public, no API key).
 *  - vPIC decodes a VIN into make, model and year (used to fill the "Add vehicle" form).
 *  - Recalls lists open safety recalls for a vehicle (shown on the vehicle details page).
 *
 * If NHTSA is slow, down or returns garbage, the failure is written to the error log and the
 * client gets a 503 with a friendly message. The rest of the application keeps working.
 */
class NhtsaService
{
    private const VIN_PATTERN = '/^[A-HJ-NPR-Z0-9]{17}$/';

    private string $vpicUrl;
    private string $recallsUrl;
    private int $timeout;
    private VehicleDao $vehicles;

    public function __construct()
    {
        $this->vpicUrl    = rtrim(Config::get('nhtsa.vpic_url', 'https://vpic.nhtsa.dot.gov/api'), '/');
        $this->recallsUrl = rtrim(Config::get('nhtsa.recalls_url', 'https://api.nhtsa.gov'), '/');
        $this->timeout    = (int)Config::get('nhtsa.timeout', 5);
        $this->vehicles   = new VehicleDao();
    }

    public function decodeVin(string $vin): array
    {
        $vin = strtoupper(trim($vin));
        if (!preg_match(self::VIN_PATTERN, $vin)) {
            throw new ApiException(400, 'A VIN has exactly 17 letters and digits (no I, O or Q).');
        }

        $data = $this->get($this->vpicUrl . '/vehicles/DecodeVinValues/' . rawurlencode($vin) . '?format=json');
        $result = $data['Results'][0] ?? [];

        $make  = trim((string)($result['Make'] ?? ''));
        $model = trim((string)($result['Model'] ?? ''));
        $year  = (int)($result['ModelYear'] ?? 0);
        if ($make === '' || $model === '' || $year === 0) {
            throw new ApiException(404, 'No vehicle data was found for this VIN.');
        }

        return [
            'vin'        => $vin,
            'make'       => $make,
            'model'      => $model,
            'year'       => $year,
            'fuel_type'  => $this->mapFuelType((string)($result['FuelTypePrimary'] ?? ''), (string)($result['ElectrificationLevel'] ?? '')),
            'body_class' => ($result['BodyClass'] ?? '') !== '' ? $result['BodyClass'] : null,
        ];
    }

    public function recalls(int $vehicleId): array
    {
        $vehicle = $this->vehicles->getById($vehicleId);
        if ($vehicle === null) {
            throw new ApiException(404, 'Vehicle not found.');
        }

        $query = http_build_query([
            'make'      => $vehicle['make'],
            'model'     => $vehicle['model'],
            'modelYear' => $vehicle['year'],
        ]);
        $data = $this->get($this->recallsUrl . '/recalls/recallsByVehicle?' . $query);

        $recalls = [];
        foreach ($data['results'] ?? [] as $item) {
            $recalls[] = [
                'campaign'      => $item['NHTSACampaignNumber'] ?? null,
                'component'     => $item['Component'] ?? null,
                'summary'       => $item['Summary'] ?? null,
                'consequence'   => $item['Consequence'] ?? null,
                'remedy'        => $item['Remedy'] ?? null,
                'manufacturer'  => $item['Manufacturer'] ?? null,
                'reported_date' => $this->parseDate($item['ReportReceivedDate'] ?? null),
            ];
        }

        return [
            'vehicle' => "{$vehicle['year']} {$vehicle['make']} {$vehicle['model']}",
            'count'   => count($recalls),
            'recalls' => $recalls,
        ];
    }

    /** One GET request with a timeout. Any failure becomes a logged 503. */
    private function get(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);

        $data = is_string($body) ? json_decode($body, true) : null;
        if ($body === false || $status >= 400 || !is_array($data)) {
            Logger::error("NHTSA request failed: $url (HTTP $status) $curlError");
            throw new ApiException(503, 'Vehicle data service is temporarily unavailable. Please try again later.');
        }

        Logger::info("NHTSA request OK: $url");
        return $data;
    }

    private function mapFuelType(string $fuel, string $electrification): ?string
    {
        if (stripos($electrification, 'hybrid') !== false || stripos($fuel, 'hybrid') !== false) return 'hybrid';
        if (stripos($fuel, 'gasoline') !== false)  return 'petrol';
        if (stripos($fuel, 'diesel') !== false)    return 'diesel';
        if (stripos($fuel, 'electric') !== false)  return 'electric';
        if (stripos($fuel, 'liquefied petroleum') !== false) return 'lpg';
        return null;
    }

    /** NHTSA sends dates as dd/mm/yyyy. */
    private function parseDate(?string $value): ?string
    {
        $date = $value ? DateTime::createFromFormat('d/m/Y', $value) : false;
        return $date ? $date->format('Y-m-d') : null;
    }
}

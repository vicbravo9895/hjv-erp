<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;

class SamsaraClient
{
    protected string $apiToken;
    protected string $apiUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retryDelay;
    protected ?array $defaultTagIds;

    public function __construct()
    {
        $this->apiToken = config('samsara.api_token');
        $this->apiUrl = config('samsara.api_url');
        $this->timeout = config('samsara.sync.timeout', 30);
        $this->retryTimes = config('samsara.sync.retry_times', 3);
        $this->retryDelay = config('samsara.sync.retry_delay', 1000);
        $this->defaultTagIds = config('samsara.sync.default_tag_ids');

        if (empty($this->apiToken)) {
            throw new \Exception('Samsara API token is not configured. Please set SAMSARA_API_TOKEN in your .env file.');
        }
    }

    /**
     * Configurar el cliente HTTP con autenticación
     */
    protected function client(): PendingRequest
    {
        return Http::withToken($this->apiToken)
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retryDelay)
            ->acceptJson();
    }

    /**
     * Obtener el feed de estadísticas de vehículos
     *
     * @param array $params Parámetros de consulta (after, limit, tagIds, etc.)
     * @return array
     */
    public function getVehicleStatsFeed(array $params = []): array
    {
        try {
            // Agregar tipos de estadísticas requeridos por Samsara
            if (!isset($params['types'])) {
                $params['types'] = 'gps,obdOdometerMeters,engineStates,fuelPercents';
            }

            // Agregar tagIds por defecto si no se especifican y están configurados
            if (!isset($params['tagIds']) && !empty($this->defaultTagIds)) {
                $params['tagIds'] = implode(',', $this->defaultTagIds);
            }

            $response = $this->client()->get($this->apiUrl . config('samsara.endpoints.vehicles'), $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Samsara API error getting vehicles', [
                'status' => $response->status(),
                'body' => $response->body(),
                'params' => $params,
            ]);

            throw new \Exception("Samsara API returned status {$response->status()}");
        } catch (\Exception $e) {
            Log::error('Exception calling Samsara vehicles API', [
                'message' => $e->getMessage(),
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Obtener el feed de estadísticas de plataformas (trailers)
     *
     * @param array $params Parámetros de consulta (after, limit, tagIds, etc.)
     * @return array
     */
    public function getTrailerStatsFeed(array $params = []): array
    {
        try {
            // Agregar tipos de estadísticas requeridos por Samsara
            if (!isset($params['types'])) {
                $params['types'] = 'gps';
            }

            // Agregar tagIds por defecto si no se especifican y están configurados
            if (!isset($params['tagIds']) && !empty($this->defaultTagIds)) {
                $params['tagIds'] = implode(',', $this->defaultTagIds);
            }

            $response = $this->client()->get($this->apiUrl . config('samsara.endpoints.trailers'), $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Samsara API error getting trailers', [
                'status' => $response->status(),
                'body' => $response->body(),
                'params' => $params,
            ]);

            throw new \Exception("Samsara API returned status {$response->status()}");
        } catch (\Exception $e) {
            Log::error('Exception calling Samsara trailers API', [
                'message' => $e->getMessage(),
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Iterar sobre todas las páginas del feed de vehículos
     *
     * @param callable $callback Función que recibe cada vehículo
     * @param int|null $limit Límite de registros por página
     * @param array|null $tagIds IDs de tags específicos para filtrar
     * @return array Estadísticas de sincronización
     */
    public function iterateVehicles(callable $callback, int $limit = null, array $tagIds = null): array
    {
        $limit = $limit ?? config('samsara.sync.page_limit', 100);
        $after = null;
        $totalProcessed = 0;
        $errors = 0;

        do {
            $params = ['limit' => $limit];
            
            if ($after) {
                $params['after'] = $after;
            }

            // Usar tagIds específicos si se proporcionan
            if (!empty($tagIds)) {
                $params['tagIds'] = implode(',', $tagIds);
            }

            try {
                $response = $this->getVehicleStatsFeed($params);
                $vehicles = $response['data'] ?? [];

                foreach ($vehicles as $vehicle) {
                    try {
                        $callback($vehicle);
                        $totalProcessed++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Error processing vehicle', [
                            'vehicle_id' => $vehicle['id'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Obtener el cursor para la siguiente página
                $after = $response['pagination']['endCursor'] ?? null;
                $hasNextPage = $response['pagination']['hasNextPage'] ?? false;
            } catch (\Exception $e) {
                Log::error('Error fetching vehicles page', [
                    'after' => $after,
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        } while ($hasNextPage && $after);

        return [
            'processed' => $totalProcessed,
            'errors' => $errors,
        ];
    }

    /**
     * Iterar sobre todas las páginas del feed de plataformas
     *
     * @param callable $callback Función que recibe cada plataforma
     * @param int|null $limit Límite de registros por página
     * @param array|null $tagIds IDs de tags específicos para filtrar
     * @return array Estadísticas de sincronización
     */
    public function iterateTrailers(callable $callback, int $limit = null, array $tagIds = null): array
    {
        $limit = $limit ?? config('samsara.sync.page_limit', 100);
        $after = null;
        $totalProcessed = 0;
        $errors = 0;

        do {
            $params = ['limit' => $limit];
            
            if ($after) {
                $params['after'] = $after;
            }

            // Usar tagIds específicos si se proporcionan
            if (!empty($tagIds)) {
                $params['tagIds'] = implode(',', $tagIds);
            }

            try {
                $response = $this->getTrailerStatsFeed($params);
                $trailers = $response['data'] ?? [];

                foreach ($trailers as $trailer) {
                    try {
                        $callback($trailer);
                        $totalProcessed++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Error processing trailer', [
                            'trailer_id' => $trailer['id'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Obtener el cursor para la siguiente página
                $after = $response['pagination']['endCursor'] ?? null;
                $hasNextPage = $response['pagination']['hasNextPage'] ?? false;
            } catch (\Exception $e) {
                Log::error('Error fetching trailers page', [
                    'after' => $after,
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        } while ($hasNextPage && $after);

        return [
            'processed' => $totalProcessed,
            'errors' => $errors,
        ];
    }

    /**
     * Get addresses from Samsara API
     *
     * @param array $params Query parameters (after, limit, tagIds, etc.)
     * @return array
     */
    public function getAddresses(array $params = []): array
    {
        try {
            // Agregar tagIds por defecto si no se especifican y están configurados
            if (!isset($params['tagIds']) && !empty($this->defaultTagIds)) {
                $params['tagIds'] = implode(',', $this->defaultTagIds);
            }

            $response = $this->client()->get($this->apiUrl . '/addresses', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Samsara API error getting addresses', [
                'status' => $response->status(),
                'body' => $response->body(),
                'params' => $params,
            ]);

            throw new \Exception("Samsara API returned status {$response->status()}");
        } catch (\Exception $e) {
            Log::error('Exception calling Samsara addresses API', [
                'message' => $e->getMessage(),
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Iterate over all pages of addresses
     *
     * @param callable $callback Function that receives each address
     * @param int|null $limit Limit of records per page
     * @param array|null $tagIds IDs de tags específicos para filtrar
     * @return array Synchronization statistics
     */
    public function iterateAddresses(callable $callback, int $limit = null, array $tagIds = null): array
    {
        $limit = $limit ?? config('samsara.sync.page_limit', 100);
        $after = null;
        $totalProcessed = 0;
        $errors = 0;

        do {
            $params = ['limit' => $limit];
            
            if ($after) {
                $params['after'] = $after;
            }

            // Usar tagIds específicos si se proporcionan
            if (!empty($tagIds)) {
                $params['tagIds'] = implode(',', $tagIds);
            }

            try {
                $response = $this->getAddresses($params);
                $addresses = $response['data'] ?? [];

                foreach ($addresses as $address) {
                    try {
                        $callback($address);
                        $totalProcessed++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Error processing address', [
                            'address_id' => $address['id'] ?? 'unknown',
                            'address_name' => $address['name'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Get cursor for next page
                $after = $response['pagination']['endCursor'] ?? null;
                $hasNextPage = $response['pagination']['hasNextPage'] ?? false;
            } catch (\Exception $e) {
                Log::error('Error fetching addresses page', [
                    'after' => $after,
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        } while ($hasNextPage && $after);

        return [
            'processed' => $totalProcessed,
            'errors' => $errors,
        ];
    }

    /**
     * Get drivers from Samsara API
     *
     * @param array $params Query parameters (after, limit, tagIds, etc.)
     * @return array
     */
    public function getDrivers(array $params = []): array
    {
        try {
            // Agregar tagIds por defecto si no se especifican y están configurados
            if (!isset($params['tagIds']) && !empty($this->defaultTagIds)) {
                $params['tagIds'] = implode(',', $this->defaultTagIds);
            }

            $response = $this->client()->get($this->apiUrl . config('samsara.endpoints.drivers', '/drivers'), $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Samsara API error getting drivers', [
                'status' => $response->status(),
                'body' => $response->body(),
                'params' => $params,
            ]);

            throw new \Exception("Samsara API returned status {$response->status()}");
        } catch (\Exception $e) {
            Log::error('Exception calling Samsara drivers API', [
                'message' => $e->getMessage(),
                'params' => $params,
            ]);
            throw $e;
        }
    }

    /**
     * Iterate over all pages of drivers
     *
     * @param callable $callback Function that receives each driver
     * @param int|null $limit Limit of records per page
     * @param array|null $tagIds IDs de tags específicos para filtrar
     * @return array Synchronization statistics
     */
    public function iterateDrivers(callable $callback, int $limit = null, array $tagIds = null): array
    {
        $limit = $limit ?? config('samsara.sync.page_limit', 100);
        $after = null;
        $totalProcessed = 0;
        $errors = 0;

        do {
            $params = ['limit' => $limit];
            
            if ($after) {
                $params['after'] = $after;
            }

            // Usar tagIds específicos si se proporcionan
            if (!empty($tagIds)) {
                $params['tagIds'] = implode(',', $tagIds);
            }

            try {
                $response = $this->getDrivers($params);
                $drivers = $response['data'] ?? [];

                foreach ($drivers as $driver) {
                    try {
                        $callback($driver);
                        $totalProcessed++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Error processing driver', [
                            'driver_id' => $driver['id'] ?? 'unknown',
                            'driver_name' => $driver['name'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Get cursor for next page
                $after = $response['pagination']['endCursor'] ?? null;
                $hasNextPage = $response['pagination']['hasNextPage'] ?? false;
            } catch (\Exception $e) {
                Log::error('Error fetching drivers page', [
                    'after' => $after,
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        } while ($hasNextPage && $after);

        return [
            'processed' => $totalProcessed,
            'errors' => $errors,
        ];
    }

    /**
     * Test API connection and authentication
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->client()->get($this->apiUrl . '/fleet/vehicles', ['limit' => 1]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Samsara API connection test failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get API rate limit information from last response
     *
     * @return array|null
     */
    public function getRateLimitInfo(): ?array
    {
        // This would need to be implemented based on Samsara's rate limit headers
        // Typically found in response headers like X-RateLimit-Remaining, X-RateLimit-Reset
        return null;
    }
}
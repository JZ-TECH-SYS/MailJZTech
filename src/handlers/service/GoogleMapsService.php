<?php

namespace src\handlers\service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use src\Config;
use src\models\Cliente_localizacao_cache;
use \core\Controller as ctrl;

class GoogleMapsService
{
    private static function client(): Client
    {
        return new Client(['timeout' => 10]);
    }

    public static function getCache($address = null, $lat = null, $lng = null, $cep = null): ?array
    {
        ctrl::log("🗄️ GET CACHE - address: $address, lat: $lat, lng: $lng, cep: $cep");

        $getcache = Cliente_localizacao_cache::select();
        if (!is_null($address)) {
            $getcache->where('endereco_completo', $address); // ✅ CORRIGIDO: endereco → endereco_completo
        }

        if (!is_null($lat) && !is_null($lng)) {
            $getcache->where('latitude', $lat)
                ->where('longitude', $lng);
        }

        if (!is_null($cep)) {
            $getcache->where('cep', $cep);
        }

        $getcache = $getcache->one();

        ctrl::log("🗄️ RESULTADO DO CACHE: " . print_r($getcache, true));

        if (empty($getcache) || empty($getcache['latitude']) || empty($getcache['longitude'])) {
            ctrl::log("❌ CACHE VAZIO OU SEM LAT/LNG");
            return null;
        }

        ctrl::log("✅ CACHE VÁLIDO - Retornando dados");
        return [
            'latitude' => $getcache['latitude'],
            'longitude' => $getcache['longitude'],
            'formatted_address' => $getcache['endereco_completo'], // ✅ CORRIGIDO: usar endereco_completo
            'place_id' => $getcache['place_id'],
            'bairro' => $getcache['bairro'],
            'cidade' => $getcache['cidade'],
            'uf' => $getcache['uf'],
            'cep' => $getcache['cep'],
            'logradouro' => $address,
            'service' => 'cache_google_maps'
        ];
    }

    /**
     * Busca endereço por CEP usando Google Maps Geocoding API
     *
     * @param string $cep CEP com ou sem formatação
     * @return array|null
     */
    public static function getAddressByCep(string $cep): ?array
    {
        try {

            $cache = self::getCache(null, null, null, $cep);
            if (!empty($cache)) {
                return $cache;
            }


            $apiKey = Config::GOOGLE_MAPS_API_KEY ?? null;
            if (!$apiKey) {
                throw new \Exception('Google Maps API Key não configurada');
            }

            // Remove formatação do CEP
            $cep = preg_replace('/\D/', '', $cep);

            if (strlen($cep) !== 8) {
                throw new \Exception('CEP deve conter 8 dígitos');
            }

            // Formata CEP para consulta
            $cepFormatted = substr($cep, 0, 5) . '-' . substr($cep, 5, 3);

            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'address' => $cepFormatted . ', Brasil',
                'key' => $apiKey,
                'language' => 'pt-BR',
                'region' => 'BR'
            ]);

            $response = self::client()->get($url);
            $data = json_decode($response->getBody(), true);

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return null;
            }

            $result = $data['results'][0];
            $addressComponents = $result['address_components'];

            // Extrai componentes do endereço
            $address = [
                'cep' => $cepFormatted,
                'logradouro' => '',
                'complemento' => '',
                'bairro' => '',
                'localidade' => '',
                'uf' => '',
                'latitude' => $result['geometry']['location']['lat'] ?? null,
                'longitude' => $result['geometry']['location']['lng'] ?? null,
                'formatted_address' => $result['formatted_address'] ?? '',
                'service' => 'google_maps'
            ];

            foreach ($addressComponents as $component) {
                $types = $component['types'];

                if (in_array('route', $types)) {
                    $address['logradouro'] = $component['long_name'];
                } elseif (in_array('sublocality', $types) || in_array('neighborhood', $types)) {
                    $address['bairro'] = $component['long_name'];
                } elseif (in_array('administrative_area_level_2', $types)) {
                    $address['localidade'] = $component['long_name'];
                } elseif (in_array('administrative_area_level_1', $types)) {
                    $address['uf'] = $component['short_name'];
                }
            }

            return $address;
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Erro ao consultar Google Maps',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Erro inesperado ao consultar Google Maps',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        }
    }

    /**
     * Busca coordenadas por endereço usando Google Maps Geocoding API
     *
     * @param string $address Endereço completo
     * @return array|null
     * (ex: logradouro, numero, bairro, cidade, uf, cep)
     */
    public static function getCoordinatesByAddress(string $address): ?array
    {
        try {
            $cache = self::getCache($address);
            if (!empty($cache)) {
                return [
                    'latitude' => $cache['latitude'],
                    'longitude' => $cache['longitude'],
                    'formatted_address' => $address,
                    'place_id' => $cache['place_id'],
                    'service' => 'cache_google_maps'
                ];
            }

            $apiKey = Config::GOOGLE_MAPS_API_KEY ?? null;
            if (!$apiKey) {
                throw new \Exception('Google Maps API Key não configurada');
            }

            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'address' => $address . ', Brasil',
                'key' => $apiKey,
                'language' => 'pt-BR',
                'region' => 'BR'
            ]);

            $response = self::client()->get($url);
            $data = json_decode($response->getBody(), true);

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return null;
            }

            $result = $data['results'][0];
            return [
                'latitude' => $result['geometry']['location']['lat'],
                'longitude' => $result['geometry']['location']['lng'],
                'formatted_address' => $result['formatted_address'],
                'place_id' => $result['place_id'] ?? null,
                'service' => 'google_maps'
            ];
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Erro ao consultar Google Maps',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Erro inesperado ao consultar Google Maps',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        }
    }

    /**
     * Calcula distância entre dois pontos usando Google Maps Distance Matrix API
     *
     * @param string $origin Origem (endereço ou lat,lng)
     * @param string $destination Destino (endereço ou lat,lng)
     * @return array|null
     */
    public static function calculateDistance(string $origin, string $destination): ?array
    {
        try {
            $apiKey = Config::GOOGLE_MAPS_API_KEY ?? null;
            if (!$apiKey) {
                throw new \Exception('Google Maps API Key não configurada');
            }

            $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query([
                'origins' => $origin,
                'destinations' => $destination,
                'key' => $apiKey,
                'language' => 'pt-BR',
                'units' => 'metric'
            ]);

            $response = self::client()->get($url);
            $data = json_decode($response->getBody(), true);

            if ($data['status'] !== 'OK' || empty($data['rows'][0]['elements'][0])) {
                return null;
            }

            $element = $data['rows'][0]['elements'][0];

            if ($element['status'] !== 'OK') {
                return null;
            }

            return [
                'distance' => [
                    'text' => $element['distance']['text'],
                    'value' => $element['distance']['value'] // em metros
                ],
                'duration' => [
                    'text' => $element['duration']['text'],
                    'value' => $element['duration']['value'] // em segundos
                ],
                'service' => 'google_maps'
            ];
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Erro ao consultar Google Maps',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Erro inesperado ao consultar Google Maps',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        }
    }



    /**
     * Geocode: converte endereço em coordenadas (lat,lng)
     * Retorna dados completos do endereço incluindo bairro
     *
     * @param string $address Endereço completo
     * @return array|null
     */
    public static function geocode(string $address): ?array
    {
        try {

            ctrl::log("🔍 GEOCODE CHAMADO: " . $address);

            $getcache = self::getCache($address);
            if (!empty($getcache)) {
                ctrl::log("✅ CACHE ENCONTRADO: " . print_r($getcache, true));
                return $getcache;
            }

            ctrl::log("⚠️ CACHE NÃO ENCONTRADO - Indo para Google Maps API");

            $apiKey = Config::GOOGLE_MAPS_API_KEY ?? null;
            if (!$apiKey) {
                throw new \Exception('Google Maps API Key não configurada');
            }

            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'address' => $address . ', Brasil',
                'key' => $apiKey,
                'language' => 'pt-BR',
                'region' => 'BR'
            ]);

            $response = self::client()->get($url);
            $data = json_decode($response->getBody(), true);

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return null;
            }

            $result = $data['results'][0];
            $components = $result['address_components'];

            // Extrair componentes do endereço
            $addressData = [
                'latitude' => $result['geometry']['location']['lat'],
                'longitude' => $result['geometry']['location']['lng'],
                'formatted_address' => $result['formatted_address'] ?? '',
                'place_id' => $result['place_id'] ?? null,
                'bairro' => '',
                'cidade' => '',
                'uf' => '',
                'cep' => '',
                'logradouro' => '',
                'service' => 'google_maps'
            ];

            foreach ($components as $component) {
                $types = $component['types'];

                if (in_array('route', $types)) {
                    $addressData['logradouro'] = $component['long_name'];
                } elseif (in_array('sublocality', $types) || in_array('sublocality_level_1', $types) || in_array('neighborhood', $types)) {
                    $addressData['bairro'] = $component['long_name'];
                } elseif (in_array('administrative_area_level_2', $types)) {
                    $addressData['cidade'] = $component['long_name'];
                } elseif (in_array('administrative_area_level_1', $types)) {
                    $addressData['uf'] = $component['short_name'];
                } elseif (in_array('postal_code', $types)) {
                    $addressData['cep'] = $component['long_name'];
                }
            }

            return $addressData;
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Erro ao fazer geocode',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Erro inesperado ao fazer geocode',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        }
    }

    /**
     * Reverse geocode: converte coordenadas (lat,lng) em endereço completo
     * Usado para validar geolocalização do cliente e extrair bairro
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return array|null
     */
    public static function reverseGeocode(float $lat, float $lng): ?array
    {
        try {

            $getcache = self::getCache(null, $lat, $lng);
            if (!empty($getcache)) {
                return $getcache;
            }

            $apiKey = Config::GOOGLE_MAPS_API_KEY ?? null;
            if (!$apiKey) {
                throw new \Exception('Google Maps API Key não configurada');
            }

            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'latlng' => "$lat,$lng",
                'key' => $apiKey,
                'language' => 'pt-BR',
                'region' => 'BR'
            ]);

            $response = self::client()->get($url);
            $data = json_decode($response->getBody(), true);

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return null;
            }

            $result = $data['results'][0];
            $components = $result['address_components'];

            // Extrair componentes do endereço
            $address = [
                'bairro' => '',
                'cidade' => '',
                'uf' => '',
                'cep' => '',
                'logradouro' => '',
                'formatted_address' => $result['formatted_address'] ?? '',
                'place_id' => $result['place_id'] ?? null,
                'latitude' => $lat,
                'longitude' => $lng,
                'service' => 'google_maps'
            ];

            foreach ($components as $component) {
                $types = $component['types'];

                if (in_array('route', $types)) {
                    $address['logradouro'] = $component['long_name'];
                } elseif (in_array('sublocality', $types) || in_array('sublocality_level_1', $types) || in_array('neighborhood', $types)) {
                    $address['bairro'] = $component['long_name'];
                } elseif (in_array('administrative_area_level_2', $types)) {
                    $address['cidade'] = $component['long_name'];
                } elseif (in_array('administrative_area_level_1', $types)) {
                    $address['uf'] = $component['short_name'];
                } elseif (in_array('postal_code', $types)) {
                    $address['cep'] = $component['long_name'];
                }
            }

            return $address;
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Erro ao fazer reverse geocode',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Erro inesperado ao fazer reverse geocode',
                'details' => $e->getMessage(),
                'service' => 'google_maps'
            ];
        }
    }
}

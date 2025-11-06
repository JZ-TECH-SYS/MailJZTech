<?php

namespace src\handlers\service;

use src\Config;

class AddressService
{
    const PROVIDER_VIACEP = 'viacep';
    const PROVIDER_GOOGLE_MAPS = 'google_maps';
    
    /**
     * Determina qual provedor usar baseado na configuração
     */
    private static function getPreferredProvider(): string
    {
        // Verifica se Google Maps está configurado
        $googleApiKey = Config::GOOGLE_MAPS_API_KEY ?? null;
        
        // Se a configuração especifica usar Google Maps e tem API key, usa Google Maps
        $useGoogleMaps = Config::USE_GOOGLE_MAPS ?? false;
        
        if ($useGoogleMaps && $googleApiKey) {
            return self::PROVIDER_GOOGLE_MAPS;
        }
        
        // Caso contrário, usa ViaCep como padrão
        return self::PROVIDER_VIACEP;
    }
    
    /**
     * Busca endereço por CEP com fallback automático
     *
     * @param string $cep CEP com ou sem formatação
     * @return array|null
     */
    public static function getAddressByCep(string $cep): ?array
    {
        $preferredProvider = self::getPreferredProvider();
        
        // Tenta primeiro com o provedor preferido
        $result = self::tryGetAddressByCep($cep, $preferredProvider);
        
        // Se não funcionou, tenta com o outro provedor
        if (!$result || (isset($result['error']) && $result['error'])) {
            $fallbackProvider = $preferredProvider === self::PROVIDER_GOOGLE_MAPS 
                ? self::PROVIDER_VIACEP 
                : self::PROVIDER_GOOGLE_MAPS;
                
            $fallbackResult = self::tryGetAddressByCep($cep, $fallbackProvider);
            
            if ($fallbackResult && !isset($fallbackResult['error'])) {
                return $fallbackResult;
            }
        }
        
        return $result;
    }
    
    /**
     * Tenta buscar endereço com um provedor específico
     */
    private static function tryGetAddressByCep(string $cep, string $provider): ?array
    {
        try {
            switch ($provider) {
                case self::PROVIDER_GOOGLE_MAPS:
                    return GoogleMapsService::getAddressByCep($cep);
                    
                case self::PROVIDER_VIACEP:
                default:
                    return ViaCepService::getAddressByCep($cep);
            }
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Erro ao consultar provedor: ' . $provider,
                'details' => $e->getMessage(),
                'service' => $provider
            ];
        }
    }
    
    /**
     * Busca CEPs por endereço (apenas ViaCep suporta esta funcionalidade)
     *
     * @param string $uf Estado (2 caracteres)
     * @param string $cidade Nome da cidade
     * @param string $logradouro Nome da rua/avenida
     * @return array|null
     */
    public static function getCepByAddress(string $uf, string $cidade, string $logradouro): ?array
    {
        // ViaCep é o único que suporta busca reversa de CEP
        return ViaCepService::getCepByAddress($uf, $cidade, $logradouro);
    }
    
    /**
     * Busca coordenadas por endereço (apenas Google Maps)
     *
     * @param string $address Endereço completo
     * @return array|null
     */
    public static function getCoordinatesByAddress(string $address): ?array
    {
        $googleApiKey = Config::GOOGLE_MAPS_API_KEY ?? null;
        
        if (!$googleApiKey) {
            return [
                'error' => true,
                'message' => 'Google Maps API Key não configurada para busca de coordenadas',
                'service' => 'google_maps'
            ];
        }
        
        return GoogleMapsService::getCoordinatesByAddress($address);
    }
    
    /**
     * Calcula distância entre dois pontos (apenas Google Maps)
     *
     * @param string $origin Origem (endereço ou lat,lng)
     * @param string $destination Destino (endereço ou lat,lng)
     * @return array|null
     */
    public static function calculateDistance(string $origin, string $destination): ?array
    {
        $googleApiKey = Config::GOOGLE_MAPS_API_KEY ?? null;
        
        if (!$googleApiKey) {
            return [
                'error' => true,
                'message' => 'Google Maps API Key não configurada para cálculo de distância',
                'service' => 'google_maps'
            ];
        }
        
        return GoogleMapsService::calculateDistance($origin, $destination);
    }
    
    /**
     * Retorna informações sobre os provedores disponíveis
     */
    public static function getAvailableProviders(): array
    {
        $googleApiKey = Config::GOOGLE_MAPS_API_KEY ?? null;
        
        return [
            'preferred' => self::getPreferredProvider(),
            'providers' => [
                self::PROVIDER_VIACEP => [
                    'name' => 'ViaCep',
                    'available' => true,
                    'features' => ['address_by_cep', 'cep_by_address']
                ],
                self::PROVIDER_GOOGLE_MAPS => [
                    'name' => 'Google Maps',
                    'available' => !empty($googleApiKey),
                    'features' => ['address_by_cep', 'coordinates_by_address', 'distance_calculation']
                ]
            ]
        ];
    }
}
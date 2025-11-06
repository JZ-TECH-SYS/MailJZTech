<?php

namespace src\handlers\service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ViaCepService
{
    const BASE_URL = 'https://viacep.com.br/ws/';
    
    private static function client(): Client
    {
        return new Client(['timeout' => 10]);
    }
    
    /**
     * Busca endereço por CEP usando ViaCep
     *
     * @param string $cep CEP com ou sem formatação
     * @return array|null
     */
    public static function getAddressByCep(string $cep): ?array
    {
        try {
            // Remove formatação do CEP
            $cep = preg_replace('/\D/', '', $cep);
            
            if (strlen($cep) !== 8) {
                throw new \Exception('CEP deve conter 8 dígitos');
            }
            
            $response = self::client()->get(self::BASE_URL . $cep . '/json/');
            $data = json_decode($response->getBody(), true);
            
            if (isset($data['erro'])) {
                return null;
            }
            
            return [
                'cep' => $data['cep'] ?? '',
                'logradouro' => $data['logradouro'] ?? '',
                'complemento' => $data['complemento'] ?? '',
                'bairro' => $data['bairro'] ?? '',
                'localidade' => $data['localidade'] ?? '',
                'uf' => $data['uf'] ?? '',
                'ibge' => $data['ibge'] ?? '',
                'gia' => $data['gia'] ?? '',
                'ddd' => $data['ddd'] ?? '',
                'siafi' => $data['siafi'] ?? '',
                'service' => 'viacep'
            ];
            
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Erro ao consultar ViaCep',
                'details' => $e->getMessage(),
                'service' => 'viacep'
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Erro inesperado ao consultar ViaCep',
                'details' => $e->getMessage(),
                'service' => 'viacep'
            ];
        }
    }
    
    /**
     * Busca CEPs por endereço usando ViaCep
     *
     * @param string $uf Estado (2 caracteres)
     * @param string $cidade Nome da cidade
     * @param string $logradouro Nome da rua/avenida
     * @return array|null
     */
    public static function getCepByAddress(string $uf, string $cidade, string $logradouro): ?array
    {
        try {
            $url = self::BASE_URL . urlencode($uf) . '/' . urlencode($cidade) . '/' . urlencode($logradouro) . '/json/';
            
            $response = self::client()->get($url);
            $data = json_decode($response->getBody(), true);
            
            if (empty($data) || isset($data['erro'])) {
                return null;
            }
            
            // Adiciona identificação do serviço
            foreach ($data as &$item) {
                $item['service'] = 'viacep';
            }
            
            return $data;
            
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Erro ao consultar ViaCep',
                'details' => $e->getMessage(),
                'service' => 'viacep'
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Erro inesperado ao consultar ViaCep',
                'details' => $e->getMessage(),
                'service' => 'viacep'
            ];
        }
    }
}
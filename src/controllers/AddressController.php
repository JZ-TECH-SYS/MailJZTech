<?php

/**
 * Controlador de Endereços
 * Responsável por gerenciar operações relacionadas a endereços e CEPs
 * usando integração com ViaCep e Google Maps
 * 
 * @author João Silva
 * @since 2024
 */

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use src\handlers\service\AddressService;

class AddressController extends ctrl
{
    /**
     * Busca endereço por CEP
     * 
     * @param array $args Array contendo o CEP
     */
    public function getAddressByCep($args)
    {
        try {
            if (empty($args['cep'])) {
                throw new Exception('CEP não informado');
            }
            
            $cep = $args['cep'];
            $data = AddressService::getAddressByCep($cep);
            
            if (!$data) {
                ctrl::response('CEP não encontrado', 404);
                return;
            }
            
            if (isset($data['error']) && $data['error']) {
                ctrl::response($data['message'], 400);
                return;
            }
            
            ctrl::response($data, 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
    
    /**
     * Busca CEPs por endereço
     * 
     * @param array $args Array contendo UF, cidade e logradouro
     */
    public function getCepByAddress($args)
    {
        try {
            if (empty($args['uf']) || empty($args['cidade']) || empty($args['logradouro'])) {
                throw new Exception('UF, cidade e logradouro são obrigatórios');
            }
            
            $data = AddressService::getCepByAddress(
                $args['uf'], 
                $args['cidade'], 
                $args['logradouro']
            );
            
            if (!$data) {
                ctrl::response('Endereço não encontrado', 404);
                return;
            }
            
            if (isset($data['error']) && $data['error']) {
                ctrl::response($data['message'], 400);
                return;
            }
            
            ctrl::response($data, 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
    
    /**
     * Busca coordenadas por endereço
     */
    public function getCoordinatesByAddress()
    {
        try {
            $body = ctrl::getBody();
            
            if (empty($body['address'])) {
                throw new Exception('Endereço não informado');
            }
            
            $data = AddressService::getCoordinatesByAddress($body['address']);
            
            if (!$data) {
                ctrl::response('Coordenadas não encontradas', 404);
                return;
            }
            
            if (isset($data['error']) && $data['error']) {
                ctrl::response($data['message'], 400);
                return;
            }
            
            ctrl::response($data, 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
    
    /**
     * Calcula distância entre dois pontos
     */
    public function calculateDistance()
    {
        try {
            $body = ctrl::getBody();
            
            if (empty($body['origin']) || empty($body['destination'])) {
                throw new Exception('Origem e destino são obrigatórios');
            }
            
            $data = AddressService::calculateDistance(
                $body['origin'],
                $body['destination']
            );
            
            if (!$data) {
                ctrl::response('Não foi possível calcular a distância', 404);
                return;
            }
            
            if (isset($data['error']) && $data['error']) {
                ctrl::response($data['message'], 400);
                return;
            }
            
            ctrl::response($data, 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
    
    /**
     * Retorna informações sobre os provedores de endereço disponíveis
     */
    public function getAvailableProviders()
    {
        try {
            $data = AddressService::getAvailableProviders();
            ctrl::response($data, 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
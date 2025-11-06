<?php

/**
 * Classe AutoCepController
 * Controlador para funcionalidades de Auto-CEP
 * 
 * @author Sistema ClickExpress
 * @since 15/08/2025
 */

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\AutoCep as AutoCepHelper;
use \src\handlers\Bairro as BairroHelper;
use \src\handlers\LeadsHandler;
use Exception;

class AutoCepController extends ctrl
{
    /**
     * Verifica se Auto-CEP está habilitado para a empresa
     */
    public function checkAutoCepStatus($args)
    {
        try {
            $idempresa = $args['idempresa'];
            $isEnabled = AutoCepHelper::isAutoCepEnabled($idempresa);
            
            ctrl::response([
                'usar_maps_auto_cep' => $isEnabled ? '1' : '0',
                'enabled' => $isEnabled
            ], 200);
        } catch (Exception $e) {
            ctrl::response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Resolve endereço para CEP e busca bairro
     */
    public function resolverEndereco()
    {
        try {
            $data = ctrl::getBody();
            
            // Verificar se o parâmetro está habilitado
            if (!isset($data['idempresa']) || !AutoCepHelper::isAutoCepEnabled($data['idempresa'])) {
                ctrl::response([
                    'success' => false,
                    'error' => 'Auto-CEP não habilitado para esta empresa'
                ], 403);
                return;
            }
            
            // Extrair dados do endereço enviado do frontend
            $endereco = $data['endereco'] ?? '';
            $idempresa = $data['idempresa'];
            
            // Parse do endereço: "Rua, Número, Cidade, Estado, País"
            $parts = explode(', ', $endereco);
            if (count($parts) < 3) {
                ctrl::response([
                    'success' => false,
                    'error' => 'Formato de endereço inválido'
                ], 400);
                return;
            }

            $rua = trim($parts[0]);
            $numero = trim($parts[1]);  
            $cidade = trim($parts[2]);
            $uf = 'SP'; // Assumindo SP por padrão, pode extrair do parts[3] se necessário

            $enderecoCompleto = "$rua, $numero";

            // Registra lead se configurado
            if (isset($idempresa)) {
                LeadsHandler::handle($idempresa, $data['id_pessoa'] ?? null);
            }

            // Usa diretamente o AutoCep helper para resolução
            $resultado = AutoCepHelper::resolverCep(
                $idempresa,
                $enderecoCompleto,
                $cidade,
                $uf,
                $data['id_pessoa'] ?? null
            );

            if (!$resultado['success']) {
                ctrl::response([
                    'success' => false,
                    'error' => $resultado['error'] ?? 'Não foi possível resolver o endereço'
                ], 400);
                return;
            }

            // Retorna no formato que o frontend espera
            ctrl::response([
                'success' => true,
                'result' => [
                    'cep' => $resultado['cep'] ?? '',
                    'bairro' => $resultado['bairro_nome'] ?? '',
                    'endereco_completo' => "$enderecoCompleto, {$cidade}, {$uf}",
                    'cidade' => $resultado['cidade'] ?? $cidade,
                    'uf' => $resultado['uf'] ?? $uf,
                    'source' => $resultado['source'] ?? 'google_maps'
                ]
            ], 200);

        } catch (Exception $e) {
            ctrl::response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Consulta CEP via APIs externas (para debug/admin)
     */
    public function consultarCepExterno()
    {
        try {
            $data = ctrl::getBody();
            
            ctrl::verificarCamposVazios($data, ['idempresa']);

            if (!AutoCepHelper::isAutoCepEnabled($data['idempresa'])) {
                ctrl::response(['error' => 'Auto-CEP desabilitado para esta empresa'], 403);
                return;
            }

            $resultado = null;

            if (isset($data['endereco'], $data['cidade'], $data['uf'])) {
                // Consulta por endereço - USA SISTEMA OTIMIZADO (Google Maps)
                $resultado = AutoCepHelper::resolverCep(
                    $data['idempresa'],
                    $data['endereco'],
                    $data['cidade'],
                    $data['uf']
                );
            } elseif (isset($data['cep'])) {
                // Consulta direta por CEP - USA Google Maps direto
                $resultado = \src\handlers\service\GoogleMapsService::getAddressByCep($data['cep']);
                
                // Padroniza resposta
                if (!empty($resultado) && !isset($resultado['error'])) {
                    $resultado = [
                        'success' => true,
                        'cep' => $resultado['cep'] ?? $data['cep'],
                        'bairro_nome' => $resultado['bairro'] ?? null,
                        'logradouro' => $resultado['logradouro'] ?? null,
                        'cidade' => $resultado['localidade'] ?? null,
                        'uf' => $resultado['uf'] ?? null,
                        'latitude' => $resultado['latitude'] ?? null,
                        'longitude' => $resultado['longitude'] ?? null,
                        'source' => 'google_maps_direct'
                    ];
                } else {
                    $resultado = ['success' => false, 'error' => 'CEP não encontrado'];
                }
            } else {
                ctrl::response(['error' => 'Forneça (endereco, cidade, uf) ou (cep)'], 400);
                return;
            }

            ctrl::response($resultado, 200);
        } catch (Exception $e) {
            ctrl::response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * NOVO: Testa Google Maps para debug (apenas admin)
     */
    public function testarTodasApis()
    {
        try {
            $data = ctrl::getBody();
            
            ctrl::verificarCamposVazios($data, ['idempresa', 'endereco', 'cidade', 'uf']);

            if (!AutoCepHelper::isAutoCepEnabled($data['idempresa'])) {
                ctrl::response(['error' => 'Auto-CEP desabilitado para esta empresa'], 403);
                return;
            }

            $resultado = AutoCepHelper::testarTodasApis(
                $data['endereco'],
                $data['cidade'],
                $data['uf'],
                $data['cep'] ?? null
            );

            ctrl::response($resultado, 200);
        } catch (Exception $e) {
            ctrl::response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * NOVO: Estatísticas do cache
     */
    public function estatisticasCache($args)
    {
        try {
            $idempresa = $args['idempresa'];
            
            if (!AutoCepHelper::isAutoCepEnabled($idempresa)) {
                ctrl::response(['error' => 'Auto-CEP desabilitado para esta empresa'], 403);
                return;
            }

            $stats = AutoCepHelper::estatisticasCache($idempresa);
            ctrl::response($stats, 200);
        } catch (Exception $e) {
            ctrl::response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Limpa cache de CEP (para admin)
     */
    public function limparCache($args)
    {
        try {
            $idempresa = $args['idempresa'];
            
            if (!AutoCepHelper::isAutoCepEnabled($idempresa)) {
                ctrl::response(['error' => 'Auto-CEP desabilitado para esta empresa'], 403);
                return;
            }

            // Limpa cache antigo (mais de 30 dias)
            $sql = "DELETE FROM cep_cache WHERE idempresa = :idempresa AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = \core\Database::getInstance()->prepare($sql);
            $stmt->execute([':idempresa' => $idempresa]);
            
            $deletedRows = $stmt->rowCount();
            
            ctrl::response([
                'message' => 'Cache limpo com sucesso',
                'deleted_entries' => $deletedRows
            ], 200);
        } catch (Exception $e) {
            ctrl::response(['error' => $e->getMessage()], 400);
        }
    }
}
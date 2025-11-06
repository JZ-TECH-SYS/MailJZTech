<?php

/**
 * Desc: Classe helper para gerenciar Auto-CEP e cache MELHORADA
 * Autor: Sistema ClickExpress
 * Data de Criação: 15/08/2025
 * Estratégia: Cache > Banco > ViaCEP > Google Maps
 */

namespace src\handlers;

use src\models\Cep_cache as CepCacheModel;
use src\models\Bairros as BairrosModel;
use src\models\Empresa_parametro as EmpresaParametroModel;
use Exception;
use core\Database as db;

class AutoCep
{
    /**
     * Verifica se o Auto-CEP está habilitado para a empresa
     */
    public static function isAutoCepEnabled($idempresa)
    {
        $param = EmpresaParametroModel::select(['valor'])
            ->where('idempresa', $idempresa)
            ->where('idparametro', 15) // ID do parâmetro usar_maps_auto_cep
            ->one();
        
        return !empty($param) && $param['valor'] == '1';
    }

    /**
     * Normaliza CEP removendo caracteres especiais
     */
    public static function normalizarCep($cep)
    {
        return preg_replace('/[^0-9]/', '', $cep);
    }

    /**
     * Busca bairro por CEP no cache ou banco
     */
    public static function buscarBairroPorCep($idempresa, $cep)
    {
        $cepNormalizado = self::normalizarCep($cep);
        
        if (strlen($cepNormalizado) !== 8) {
            return null;
        }

        // Primeiro tenta buscar no cadastro de bairros
        $bairro = BairrosModel::select([
            'bairros.idbairro',
            'bairros.nome',
            'bairros.taxa',
            'bairros.cep',
            'c.nome as cidade',
            'es.uf'
        ])
        ->leftjoin('cidade as c', 'c.id', '=', 'bairros.idcidade')
        ->leftjoin('estado as es', 'es.id', '=', 'c.uf')
        ->where('bairros.idempresa', $idempresa)
        ->where('bairros.cep', $cepNormalizado)
        ->where('bairros.status', 1) // Apenas bairros ativos
        ->one();

        if (!empty($bairro)) {
            return [
                'found' => true,
                'locked' => true,
                'bairro' => $bairro,
                'source' => 'database'
            ];
        }

        return [
            'found' => false,
            'locked' => false,
            'bairro' => null,
            'source' => null
        ];
    }

    /**
     * Consulta ViaCEP usando o serviço unificado
     */
    public static function consultarViaCep($cep)
    {
        try {
            $cepNormalizado = self::normalizarCep($cep);
            
            if (strlen($cepNormalizado) !== 8) {
                return ['success' => false, 'error' => 'CEP inválido'];
            }

            $result = \src\handlers\service\ViaCepService::getAddressByCep($cepNormalizado);

            if (!empty($result) && !isset($result['error'])) {
                return [
                    'success' => true,
                    'cep' => self::normalizarCep($result['cep'] ?? $cepNormalizado),
                    'bairro_nome' => $result['bairro'] ?? null,
                    'logradouro' => $result['logradouro'] ?? null,
                    'cidade' => $result['localidade'] ?? null,
                    'uf' => $result['uf'] ?? null,
                    'latitude' => null,
                    'longitude' => null,
                    'confidence' => 0.9,
                    'source' => 'viacep'
                ];
            }

            return ['success' => false, 'error' => 'CEP não encontrado no ViaCEP'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro no ViaCEP: ' . $e->getMessage()];
        }
    }

    /**
     * Consulta CEP via Google Maps usando o serviço unificado
     */
    public static function consultarCepMapsUnificado($endereco, $cidade, $uf)
    {
        try {
            $query = trim("$endereco, $cidade, $uf, Brasil");
            
            // Primeiro tenta por coordenadas (mais preciso)
            $coordResult = \src\handlers\service\GoogleMapsService::getCoordinatesByAddress($query);
            
            if (!empty($coordResult) && !isset($coordResult['error'])) {
                return [
                    'success' => true,
                    'cep' => null, // Coordenadas não retornam CEP diretamente
                    'bairro_nome' => null,
                    'logradouro' => $coordResult['formatted_address'] ?? $endereco,
                    'cidade' => $cidade,
                    'uf' => $uf,
                    'latitude' => $coordResult['latitude'],
                    'longitude' => $coordResult['longitude'],
                    'confidence' => 0.8,
                    'source' => 'google_coordinates'
                ];
            }

            return ['success' => false, 'error' => 'Google Maps não retornou resultado válido'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro no Google Maps: ' . $e->getMessage()];
        }
    }

    /**
     * Consulta ViaCEP por busca reversa (endereço -> CEP)
     */
    public static function consultarViaCepReverso($uf, $cidade, $endereco)
    {
        try {
            $result = \src\handlers\service\ViaCepService::getCepByAddress($uf, $cidade, $endereco);
            
            if (!empty($result) && !isset($result['error']) && is_array($result)) {
                // ViaCEP retorna array de resultados
                $primeiro = is_array($result[0] ?? null) ? $result[0] : $result;
                
                if (!empty($primeiro) && !isset($primeiro['erro'])) {
                    return [
                        'success' => true,
                        'cep' => self::normalizarCep($primeiro['cep'] ?? ''),
                        'bairro_nome' => $primeiro['bairro'] ?? null,
                        'logradouro' => $primeiro['logradouro'] ?? $endereco,
                        'cidade' => $primeiro['localidade'] ?? $cidade,
                        'uf' => $primeiro['uf'] ?? $uf,
                        'latitude' => null,
                        'longitude' => null,
                        'confidence' => 0.85,
                        'source' => 'viacep_reverse'
                    ];
                }
            }

            return ['success' => false, 'error' => 'ViaCEP busca reversa sem resultado'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro no ViaCEP reverso: ' . $e->getMessage()];
        }
    }

    /**
     * Salva resultado no cache
     */
    public static function salvarCache($idempresa, $dadosCep, $queryOriginal, $idPessoa = null)
    {
        try {
            if (!$dadosCep['success']) {
                return false;
            }

            $dadosCache = [
                'idempresa' => $idempresa,
                'id_pessoa' => $idPessoa,
                'cep' => $dadosCep['cep'],
                'bairro_nome' => $dadosCep['bairro_nome'],
                'logradouro' => $dadosCep['logradouro'],
                'cidade' => $dadosCep['cidade'],
                'uf' => $dadosCep['uf'],
                'latitude' => $dadosCep['latitude'],
                'longitude' => $dadosCep['longitude'],
                'source' => $dadosCep['source'],
                'confidence' => $dadosCep['confidence'],
                'query_normalized' => strtolower(trim($queryOriginal)),
                'hits' => 1,
                'last_hit_at' => date('Y-m-d H:i:s')
            ];

            CepCacheModel::insert($dadosCache)->execute();
            return true;
        } catch (Exception $e) {
            // Log do erro mas não falha a operação principal
            error_log("Erro ao salvar cache CEP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca no cache COM MÚLTIPLAS ESTRATÉGIAS
     */
    public static function buscarCache($idempresa, $query, $cep = null)
    {
        try {
            $queryNormalizada = strtolower(trim($query));
            
            // ESTRATÉGIA 1: Busca exata por query normalizada
            $resultado = CepCacheModel::select()
                ->where('idempresa', $idempresa)
                ->where('query_normalized', $queryNormalizada)
                ->where('created_at', '>', date('Y-m-d H:i:s', strtotime('-6 months'))) // Cache válido por 6 meses
                ->orderBy('last_hit_at', 'DESC')
                ->one();

            // ESTRATÉGIA 2: Se não achou, busca por CEP direto se fornecido
            if (empty($resultado) && $cep) {
                $cepNormalizado = self::normalizarCep($cep);
                if (strlen($cepNormalizado) === 8) {
                    $resultado = CepCacheModel::select()
                        ->where('idempresa', $idempresa)
                        ->where('cep', $cepNormalizado)
                        ->where('created_at', '>', date('Y-m-d H:i:s', strtotime('-6 months')))
                        ->orderBy('last_hit_at', 'DESC')
                        ->one();
                }
            }

            // ESTRATÉGIA 3: Busca SIMILAR por partes da query (fuzzy search)
            if (empty($resultado)) {
                $partes = explode(',', $queryNormalizada);
                $enderecoParte = trim($partes[0] ?? '');
                $cidadeParte = trim($partes[1] ?? '');
                
                if (strlen($enderecoParte) > 5 && strlen($cidadeParte) > 3) {
                    $resultado = CepCacheModel::select()
                        ->where('idempresa', $idempresa)
                        ->where('query_normalized', 'LIKE', "%{$enderecoParte}%")
                        ->where('query_normalized', 'LIKE', "%{$cidadeParte}%")
                        ->where('created_at', '>', date('Y-m-d H:i:s', strtotime('-3 months'))) // Cache mais recente para fuzzy
                        ->orderBy('confidence', 'DESC')
                        ->orderBy('hits', 'DESC')
                        ->one();
                }
            }

            if (!empty($resultado)) {
                // Atualiza hits e last_hit_at
                CepCacheModel::update([
                    'hits' => $resultado['hits'] + 1,
                    'last_hit_at' => date('Y-m-d H:i:s')
                ])
                ->where('id_cep_cache', $resultado['id_cep_cache'])
                ->execute();

                return [
                    'success' => true,
                    'cep' => $resultado['cep'],
                    'bairro_nome' => $resultado['bairro_nome'],
                    'logradouro' => $resultado['logradouro'],
                    'cidade' => $resultado['cidade'],
                    'uf' => $resultado['uf'],
                    'latitude' => $resultado['latitude'],
                    'longitude' => $resultado['longitude'],
                    'confidence' => $resultado['confidence'],
                    'source' => $resultado['source'] . '_cache',
                    'cache_age_days' => floor((time() - strtotime($resultado['created_at'])) / 86400),
                    'hit_count' => $resultado['hits'] + 1
                ];
            }

            return ['success' => false, 'cache_miss' => true];
        } catch (Exception $e) {
            error_log("Erro na busca de cache CEP: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'cache_error' => true];
        }
    }

    /**
     * Resolve CEP completo com cache PRIORITÁRIO e fallback entre APIs
     * ESTRATÉGIA: Cache > Banco > ViaCEP > Google Maps > Erro
     */
    public static function resolverCep($idempresa, $endereco, $cidade, $uf, $idPessoa = null)
    {
        if (!self::isAutoCepEnabled($idempresa)) {
            return ['success' => false, 'error' => 'Auto-CEP desabilitado'];
        }

        $queryOriginal = "$endereco, $cidade, $uf";

        // 1. ✅ SEMPRE tenta buscar no CACHE PRIMEIRO (prioridade máxima)
        $cacheResult = self::buscarCache($idempresa, $queryOriginal);
        if ($cacheResult['success']) {
            return array_merge($cacheResult, ['cache_hit' => true]);
        }

        // 2. ✅ Se não tem no cache, busca no BANCO DE BAIRROS por CEP conhecido
        $cepExtraido = null;
        if (preg_match('/\b\d{5}-?\d{3}\b/', $endereco, $matches)) {
            $cepExtraido = self::normalizarCep($matches[0]);
            $bairroExistente = self::buscarBairroPorCep($idempresa, $cepExtraido);
            
            if ($bairroExistente['found']) {
                $resultFromBairro = [
                    'success' => true,
                    'cep' => $cepExtraido,
                    'bairro_nome' => $bairroExistente['bairro']['nome'],
                    'logradouro' => $endereco,
                    'cidade' => $cidade,
                    'uf' => $uf,
                    'latitude' => null,
                    'longitude' => null,
                    'confidence' => 1.0,
                    'source' => 'database_bairro'
                ];
                
                self::salvarCache($idempresa, $resultFromBairro, $queryOriginal, $idPessoa);
                return array_merge($resultFromBairro, ['database_hit' => true]);
            }
        }

        // 3. ✅ Tenta ViaCEP PRIMEIRO (gratuito, confiável, rápido)
        if ($cepExtraido) {
            $viaCepResult = self::consultarViaCep($cepExtraido);
            if ($viaCepResult['success']) {
                self::salvarCache($idempresa, $viaCepResult, $queryOriginal, $idPessoa);
                return array_merge($viaCepResult, ['viacep_hit' => true]);
            }
        }

        // 4. ✅ Fallback: Google Maps (mais poderoso, mas usa quota)
        $mapsResult = self::consultarCepMapsUnificado($endereco, $cidade, $uf);
        if ($mapsResult['success']) {
            self::salvarCache($idempresa, $mapsResult, $queryOriginal, $idPessoa);
            return array_merge($mapsResult, ['googlemaps_hit' => true]);
        }

        // 5. ✅ Último recurso: ViaCEP busca reversa (se não tinha CEP antes)
        if (!$cepExtraido) {
            $viaCepBusca = self::consultarViaCepReverso($uf, $cidade, $endereco);
            if ($viaCepBusca['success']) {
                self::salvarCache($idempresa, $viaCepBusca, $queryOriginal, $idPessoa);
                return array_merge($viaCepBusca, ['viacep_reverse_hit' => true]);
            }
        }

        return [
            'success' => false, 
            'error' => 'Não foi possível resolver o CEP com nenhuma API', 
            'tried_cache' => true,
            'tried_database' => !empty($cepExtraido),
            'tried_viacep' => true,
            'tried_googlemaps' => true
        ];
    }

    /**
     * Método de debug para testar todas as APIs com um endereço
     * USAR APENAS PARA TESTES/DEBUG
     */
    public static function testarTodasApis($endereco, $cidade, $uf, $cep = null)
    {
        $resultados = [
            'endereco_testado' => "$endereco, $cidade, $uf",
            'cep_testado' => $cep,
            'timestamp' => date('Y-m-d H:i:s'),
            'resultados' => []
        ];

        // Testa ViaCEP direto por CEP
        if ($cep) {
            $resultados['resultados']['viacep_direto'] = self::consultarViaCep($cep);
        }

        // Testa ViaCEP busca reversa
        $resultados['resultados']['viacep_reverso'] = self::consultarViaCepReverso($uf, $cidade, $endereco);

        // Testa Google Maps
        $resultados['resultados']['google_maps'] = self::consultarCepMapsUnificado($endereco, $cidade, $uf);

        return $resultados;
    }

    /**
     * Limpa cache antigo (mais de X dias)
     */
    public static function limparCacheAntigo($idempresa, $diasParaManterCache = 180)
    {
        try {
            $sql = "DELETE FROM cep_cache 
                    WHERE idempresa = :idempresa 
                    AND created_at < DATE_SUB(NOW(), INTERVAL :dias DAY)";
            
            $stmt = db::getInstance()->prepare($sql);
            $stmt->execute([
                ':idempresa' => $idempresa,
                ':dias' => $diasParaManterCache
            ]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Erro ao limpar cache CEP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Estatísticas do cache para monitoramento
     */
    public static function estatisticasCache($idempresa)
    {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_entradas,
                    COUNT(DISTINCT cep) as ceps_unicos,
                    SUM(hits) as total_hits,
                    AVG(confidence) as confianca_media,
                    source,
                    COUNT(*) as count_por_source
                FROM cep_cache 
                WHERE idempresa = :idempresa 
                GROUP BY source
                ORDER BY count_por_source DESC
            ";
            
            $stmt = db::getInstance()->prepare($sql);
            $stmt->execute([':idempresa' => $idempresa]);
            
            return [
                'success' => true,
                'estatisticas' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

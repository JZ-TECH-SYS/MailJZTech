<?php

/**
 * Classe CepController
 * Controlador específico para operações de CEP com preenchimento automático
 * 
 * @author Sistema ClickExpress
 * @since 18/08/2025
 */

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\AutoCep as AutoCepHandler;
use \src\handlers\Bairro as BairroHandler;
use \src\models\Cidade as CidadeModel;
use Exception;

class CepController extends ctrl
{
    /**
     * Busca dados completos do CEP: logradouro, bairro, cidade + bairros cadastrados
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getCepData($args)
    {
        try {
            $idempresa = $args['idempresa'];
            $cep = $_GET['cep'] ?? null;
            
            if (!$cep) {
                ctrl::response(['success' => false, 'error' => 'CEP é obrigatório'], 400);
                return;
            }

            // Verifica se Auto-CEP está habilitado
            if (!AutoCepHandler::isAutoCepEnabled($idempresa)) {
                ctrl::response(['success' => false, 'error' => 'Auto-CEP não habilitado'], 400);
                return;
            }

            // Limpa o CEP
            $cepLimpo = preg_replace('/[^0-9]/', '', $cep);
            
            if (strlen($cepLimpo) !== 8) {
                ctrl::response(['success' => false, 'error' => 'CEP inválido'], 400);
                return;
            }

            // 1. Busca dados do CEP (cache ou ViaCEP)
            $cacheResult = AutoCepHandler::buscarCachePorCep($idempresa, $cepLimpo);
            $dadosEndereco = null;
            
            if ($cacheResult['success']) {
                $dadosEndereco = $cacheResult['dados'];
            } else {
                $resultadoViaCep = AutoCepHandler::consultarViaCep($cepLimpo);
                if ($resultadoViaCep['success']) {
                    $dadosEndereco = $resultadoViaCep['dados'];
                    AutoCepHandler::salvarCacheCep($idempresa, $dadosEndereco, $cepLimpo);
                }
            }

            if (!$dadosEndereco) {
                ctrl::response(['success' => false, 'error' => 'CEP não encontrado'], 404);
                return;
            }

            // 2. Busca a cidade correspondente no sistema (busca mais precisa)
            $cidadeEncontrada = CidadeModel::select(['id as idcidade', 'nome'])
                ->where('nome', '=', $dadosEndereco['localidade'])
                ->one();
                
            // Se não encontrou exato, tenta busca aproximada
            if (!$cidadeEncontrada) {
                $cidadeEncontrada = CidadeModel::select(['id as idcidade', 'nome'])
                    ->where('nome', 'LIKE', $dadosEndereco['localidade'] . '%')
                    ->one();
            }
            
            // Se ainda não encontrou, tenta busca contendo (mais flexível)
            if (!$cidadeEncontrada) {
                $cidadeEncontrada = CidadeModel::select(['id as idcidade', 'nome'])
                    ->where('nome', 'LIKE', '%' . $dadosEndereco['localidade'] . '%')
                    ->one();
            }

            if (!$cidadeEncontrada) {
                ctrl::response([
                    'success' => false, 
                    'error' => 'Cidade não encontrada no sistema: ' . $dadosEndereco['localidade']
                ], 404);
                return;
            }

            // 3. Busca bairros da cidade que fazem match usando algoritmo inteligente
            $bairrosCidade = BairroHandler::getBairros($idempresa, $cidadeEncontrada['idcidade']);
            
            // IMPORTANTE: Filtrar apenas bairros VÁLIDOS (idbairro > 0)
            $bairrosCidadeValidos = array_filter($bairrosCidade, function($bairro) {
                return isset($bairro['idbairro']) && (int)$bairro['idbairro'] > 0;
            });
            
            $bairrosComScore = [];
            $melhorMatch = null;
            $melhorScore = 0;
            
            if (!empty($dadosEndereco['bairro'])) {
                foreach ($bairrosCidadeValidos as $bairro) {
                    $score = BairroHandler::calculateMatchScore($bairro['nome'], $dadosEndereco['bairro']);
                    
                    if ($score >= 60) { // Score mínimo para considerar um match
                        $bairroComScore = $bairro;
                        $bairroComScore['match_score'] = $score;
                        $bairrosComScore[] = $bairroComScore;
                        
                        // Rastreia o melhor match
                        if ($score > $melhorScore) {
                            $melhorScore = $score;
                            $melhorMatch = $bairroComScore;
                        }
                    }
                }
                
                // Ordena por score (maior primeiro)
                usort($bairrosComScore, function($a, $b) {
                    return $b['match_score'] <=> $a['match_score'];
                });
            }

            // 4. Se não encontrou bairros com score, retorna todos os bairros VÁLIDOS da cidade
            if (empty($bairrosComScore)) {
                $bairrosComScore = $bairrosCidadeValidos;
            }

            // 5. Define comportamento de auto-seleção
            $autoSelecionar = false;
            $bloquearCampo = false;
            
            // Se encontrou bairros com score alto (>= 80), auto-seleciona e bloqueia
            if (!empty($bairrosComScore) && $melhorMatch && $melhorScore >= 80) {
                $autoSelecionar = true;
                $bloquearCampo = true;
            }
            // Se encontrou apenas 1 bairro, auto-seleciona e bloqueia
            else if (count($bairrosComScore) === 1) {
                $autoSelecionar = true;
                $bloquearCampo = true;
                $melhorMatch = $bairrosComScore[0];
            }
            // Se encontrou múltiplos bairros mas com score médio (60-79), sugere mas NÃO bloqueia
            else if (!empty($bairrosComScore) && $melhorMatch) {
                $autoSelecionar = true;
                $bloquearCampo = false; // Permite trocar manualmente
            }

            // 6. Retorna dados completos com informações de auto-seleção
            // IMPORTANTE: Modificamos apenas os dados de endereço do ViaCEP para não incluir o bairro
            // Os bairros vêm sempre do nosso sistema
            $dadosEnderecoLimpo = [
                'cep' => $dadosEndereco['cep'],
                'logradouro' => $dadosEndereco['logradouro'],
                'localidade' => $dadosEndereco['localidade'],
                'uf' => $dadosEndereco['uf']
                // Removemos 'bairro' do ViaCEP para evitar confusão
            ];
            
            ctrl::response([
                'success' => true,
                'dados' => $dadosEnderecoLimpo, // Dados do ViaCEP SEM o bairro
                'cidade' => $cidadeEncontrada,   // Cidade do nosso sistema
                'bairros' => $bairrosComScore,   // Bairros do nosso sistema com scores
                'melhor_match' => $melhorMatch,  // Melhor bairro do nosso sistema
                'auto_selecionar_bairro' => $autoSelecionar,
                'bloquear_campo_bairro' => $bloquearCampo,
                'auto_preenchido' => true,
                'bairro_viacep_original' => $dadosEndereco['bairro'] // Para debug
            ], 200);

        } catch (Exception $e) {
            error_log("Erro getCepData: " . $e->getMessage());
            ctrl::response(['success' => false, 'error' => 'Erro interno do servidor'], 500);
        }
    }
}

<?php

/**
 * Classe LocalizacaoController
 * Controlador responsável por geolocalização e QR Code para delivery
 * 
 * @author ClickExpress Team
 * @since 29/10/2025
 */

namespace src\controllers;

use \core\Controller as ctrl;
use src\handlers\service\GoogleMapsService;
use src\models\Cliente_localizacao_cache;
use src\models\Bairros;
use src\models\Pedido_venda;
use Exception;
use GuzzleHttp\Client;

class LocalizacaoController extends ctrl
{
    /**
     * POST /geocodificarEndereco
     * 
     * Geocodifica um endereço (converte endereço texto em coordenadas lat/lng)
     * Usado no fluxo de checkout para localizar o endereço informado pelo cliente
     * 
     * @param array $body {
     *   idempresa: int,
     *   endereco_completo?: string,
     *   rua?: string,
     *   numero?: string,
     *   bairro?: string,
     *   cidade?: string,
     *   estado?: string,
     *   cep?: string
     * }
     * 
     * @return array {
     *   latitude: float,
     *   longitude: float,
     *   bairro: string,
     *   endereco_formatado: string,
     *   place_id: string
     * }
     */
    public function geocodificarEndereco()
    {
        try {
            $data = ctrl::getBody();
            
            // Validar campos obrigatórios
            ctrl::verificarCamposVazios($data, ['idempresa']);
            
            $idempresa = (int) $data['idempresa'];
            
            // Montar endereço completo para geocodificação
            $enderecoCompleto = $data['endereco_completo'] ?? '';
            
            // Se não tiver endereço completo, montar a partir das partes
            if (empty($enderecoCompleto)) {
                $partes = [];
                
                if (!empty($data['rua'])) {
                    $partes[] = $data['rua'];
                }
                if (!empty($data['numero'])) {
                    $partes[] = $data['numero'];
                }
                if (!empty($data['bairro'])) {
                    $partes[] = $data['bairro'];
                }
                if (!empty($data['cidade'])) {
                    $partes[] = $data['cidade'];
                }
                if (!empty($data['estado'])) {
                    $partes[] = $data['estado'];
                }
                if (!empty($data['cep'])) {
                    $partes[] = 'CEP ' . $data['cep'];
                }
                
                $enderecoCompleto = implode(', ', $partes);
            }
            
            if (empty($enderecoCompleto)) {
                ctrl::response('Endereço não informado', 400);
            }
        
            $geoData = GoogleMapsService::geocode($enderecoCompleto);
            if (!$geoData || isset($geoData['error'])) {
                ctrl::response('Não foi possível localizar o endereço. Verifique se está correto.', 400);
            }

            \src\models\Cliente_localizacao_cache::saveOrUpdate([
                'endereco_completo' => $enderecoCompleto,
                'cep'               => $data['cep'] ?? null,
                'numero'            => $data['numero'] ?? '',
                'complemento'       => $data['complemento'] ?? null,
                'bairro'            => $bairro['nome'] ?? '',
                'cidade'            => $cidade['nome'] ?? '',
                'uf'                => $estado['uf'] ?? '',
                'latitude'          => $geoData['latitude'],
                'longitude'         => $geoData['longitude'],
                'place_id'          => $geoData['place_id'],
                'precision_source'  => 'geolocation',
                'validated_at'      => date('Y-m-d H:i:s')
            ]);
            
            $result = [
                'latitude' => $geoData['latitude'],
                'longitude' => $geoData['longitude'],
                'bairro' => $geoData['bairro'],
                'cidade' => $geoData['cidade'],
                'uf' => $geoData['uf'],
                'endereco_formatado' => $geoData['formatted_address'],
                'place_id' => $geoData['place_id']
            ];
            
            ctrl::response($result, 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
    
    /**
     * POST /validarGeolocalizacao
     * 
     * Valida coordenadas GPS do cliente e retorna bairro + taxa de entrega
     * Usado quando cliente clica no botão de geolocalização no checkout
     */
    public function validarGeolocalizacao()
    {
        try {
            $data = ctrl::getBody();
            
            // Validar campos obrigatórios
            ctrl::verificarCamposVazios($data, ['idempresa', 'latitude', 'longitude']);
            
            $idempresa = (int) $data['idempresa'];
            $lat = (float) $data['latitude'];
            $lng = (float) $data['longitude'];
            $bairroInformado = $data['bairro'] ?? '';
            
            // Reverse geocode usando Google Maps
            $geoData = GoogleMapsService::reverseGeocode($lat, $lng);
            
            if (!$geoData || isset($geoData['error'])) {
                ctrl::response('Não foi possível validar a localização. Tente novamente.', 400);
            }
            
            $bairroDetectado = $geoData['bairro'];
            $cidade = $geoData['cidade'];
            $uf = $geoData['uf'];
            
            // Buscar taxa de entrega para o bairro detectado
            $bairroDb = Bairros::select()
                ->where('idempresa', $idempresa)
                ->where('nome', $bairroDetectado)
                ->get();
            
            $taxaEntrega = 0;
            $idbairro = null;
            
            if (!empty($bairroDb)) {
                $taxaEntrega = (float) $bairroDb[0]['taxa'];
                $idbairro = (int) $bairroDb[0]['idbairro'];
            }
            
            // Verificar divergência entre bairro informado e detectado
            $divergencia = false;
            if ($bairroInformado && 
                strtolower(trim($bairroInformado)) !== strtolower(trim($bairroDetectado))) {
                $divergencia = true;
            }
            
            $result = [
                'bairro' => $bairroDetectado,
                'cidade' => $cidade,
                'uf' => $uf,
                'idbairro' => $idbairro,
                'taxa_entrega' => $taxaEntrega,
                'formatted_address' => $geoData['formatted_address'],
                'place_id' => $geoData['place_id'],
                'divergencia' => $divergencia,
                'bairro_informado' => $bairroInformado
            ];
            
            ctrl::response($result, 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
    
    /**
     * POST /salvarLocalizacaoCliente
     * 
     * Salva cache de localização do cliente após validação
     * Observação de fluxo: preferir chamar SOMENTE na finalização do pedido.
     * Caso o front queira apenas validar/antecipar (sem gravar), envie persist=false.
     */
    public function salvarLocalizacaoCliente()
    {
        try {
            $data = ctrl::getBody();
            
            // Controle de persistência: se persist=false, não grava em banco (dry-run)
            $persist = true;
            if (isset($data['persist'])) {
                $persist = filter_var($data['persist'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $persist = $persist === null ? true : $persist; // fallback para true
            }

            // Se for dry-run, não exigir idcliente nem validar campos obrigatórios
            if ($persist !== true) {
                $dadosPreview = [
                    'endereco_completo' => $data['endereco_completo'] ?? null,
                    'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                    'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                    'precision_source' => $data['precision_source'] ?? 'manual',
                ];

                $mapsUrl = '';
                if (!empty($dadosPreview['latitude']) && !empty($dadosPreview['longitude'])) {
                    $mapsUrl = sprintf('https://www.google.com/maps/dir/?api=1&destination=%s,%s', $dadosPreview['latitude'], $dadosPreview['longitude']);
                } elseif (!empty($dadosPreview['endereco_completo'])) {
                    $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($dadosPreview['endereco_completo']);
                }

                ctrl::response([
                    'persisted' => false,
                    'preview' => [
                        'maps_url' => $mapsUrl,
                        'precision_source' => $dadosPreview['precision_source'],
                        'has_coordinates' => !empty($dadosPreview['latitude']) && !empty($dadosPreview['longitude'])
                    ]
                ], 200);
                return;
            }

            // Validar campos obrigatórios para persistir
            ctrl::verificarCamposVazios($data, ['idempresa', 'idcliente']);
            
            // Valores válidos do ENUM: 'geolocation', 'geocode', 'cep', 'manual'
            $validPrecisionSources = ['geolocation', 'geocode', 'cep', 'manual'];
            $precisionSource = $data['precision_source'] ?? 'manual';
            
            // Sanitizar - se não for válido, usar 'manual'
            if (!in_array($precisionSource, $validPrecisionSources)) {
                $precisionSource = 'manual';
            }
            
            $dados = [
                'idempresa' => (int) $data['idempresa'],
                'idcliente' => (int) $data['idcliente'],
                'endereco_completo' => $data['endereco_completo'] ?? null,
                'cep' => $data['cep'] ?? null,
                'numero' => $data['numero'] ?? '',
                'complemento' => $data['complemento'] ?? null,
                'bairro' => $data['bairro'] ?? '',
                'cidade' => $data['cidade'] ?? '',
                'uf' => $data['uf'] ?? '',
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                'place_id' => $data['place_id'] ?? null,
                'precision_source' => $precisionSource,
                'validated_at' => isset($data['latitude']) && $data['latitude'] 
                    ? date('Y-m-d H:i:s') 
                    : null
            ];

            $idcache = Cliente_localizacao_cache::saveOrUpdate($dados);
            ctrl::response(['idcache' => $idcache, 'persisted' => true], 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
    
    /**
     * GET /gerarQRCodeEntrega/{idpedidovenda}
     * 
     * Gera QR Code com URL do Google Maps para entrega
     * Prioridade: lat,lng > endereço texto
     */
    public function gerarQRCodeEntrega($args)
    {
        try {
            $idpedidovenda = (int) $args['idpedidovenda'];
            
            // Buscar pedido
            $pedido = Pedido_venda::select()
                ->where('idpedidovenda', $idpedidovenda)
                ->get();
            
            if (empty($pedido)) {
                ctrl::response('Pedido não encontrado', 404);
            }
            
            $pedido = $pedido[0];
            
            // Decodificar obs para checar geolocalização salva na finalização
            $obs = [];
            if (!empty($pedido['obs'])) {
                $decoded = json_decode($pedido['obs'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $obs = $decoded;
                }
            }

            // Buscar cache de localização do cliente (se existir)
            $cache = null;
            if (!empty($pedido['idcliente'])) {
                $cache = Cliente_localizacao_cache::getByCliente(
                    $pedido['idempresa'],
                    $pedido['idcliente']
                );
            }
            
            // Montar URL do Google Maps
            $mapsUrl = '';
            $enderecoTexto = '';
            $precisionSource = 'manual';
            
            // PRIORIDADE 0: Coordenadas salvas no pedido (finalização)
            if (!empty($obs['geo']['latitude']) && !empty($obs['geo']['longitude'])) {
                $mapsUrl = sprintf(
                    'https://www.google.com/maps/dir/?api=1&destination=%s,%s',
                    $obs['geo']['latitude'],
                    $obs['geo']['longitude']
                );
                $enderecoTexto = $obs['endereco'] ?? ($cache['endereco_completo'] ?? '');
                $precisionSource = $obs['geo']['precision_source'] ?? 'geolocation';

            } elseif ($cache && $cache['latitude'] && $cache['longitude']) {
                // PRIORIDADE 1: Coordenadas GPS exatas
                $mapsUrl = sprintf(
                    'https://www.google.com/maps/dir/?api=1&destination=%s,%s',
                    $cache['latitude'],
                    $cache['longitude']
                );
                $enderecoTexto = $cache['endereco_completo'];
                $precisionSource = $cache['precision_source'];
                
            } elseif ($cache && $cache['endereco_completo']) {
                // PRIORIDADE 2: Endereço texto do cache
                $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . 
                           urlencode($cache['endereco_completo']);
                $enderecoTexto = $cache['endereco_completo'];
                $precisionSource = 'manual';
                
            } else {
                // Sem cache ou endereço
                ctrl::response('Endereço de entrega não encontrado', 404);
            }
            
            // Gerar QR Code usando biblioteca Endroid
            $qrCode = \Endroid\QrCode\Builder\Builder::create()
                ->data($mapsUrl)
                ->size(300)
                ->margin(10)
                ->build();
            
            $qrBase64 = base64_encode($qrCode->getString());
            
            $result = [
                'qr_code_base64' => 'data:image/png;base64,' . $qrBase64,
                'maps_url' => $mapsUrl,
                'endereco_texto' => $enderecoTexto,
                'nome' => $pedido['nome'],
                'precision_source' => $precisionSource,
                'has_coordinates' => ($cache && $cache['latitude'] && $cache['longitude']) ? true : false
            ];
            
            ctrl::response($result, 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}

<?php

/**
 * Classe ParametrizacaoController
 * Controlador de Parametros responsável por gerenciar operações relacionadas a Parametros.
 * 
 * @author João Silva
 * @since 07/07/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use core\Controller;
use Exception;
use src\Config;
use src\handlers\CuponRegra;
use src\handlers\Help;
use src\handlers\MsgMyzap;
use \src\handlers\service\MyZap;
use src\models\Empresa;
use src\models\Empresa_parametro;

class ParametrizacaoController extends ctrl
{
    /**
     * Retorna todos as infos de parametro da empresa. por nome
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getInfoPsedidoOn($args)
    {
        if (is_numeric($args['nome_empresa'])) ctrl::response('nome da empresa deve ser string', 400);
        $data = Help::getInfoPsedidoOn($args['nome_empresa']);
        ctrl::response($data, 200);
    }

    /**
     * Retorna todos as infos de parametro da empresa. por id
     * Agora incluindo lógica de autenticação diretamente na rota
     * 
     * @param array $args Array contendo o ID da empresa;
     */
    public function getInfoSiteParams($args)
    {
        try {
            // Incluir lógica de autenticação diretamente na rota se necessário
            // Para leads (potenciais clientes), permitimos acesso sem autenticação completa
            $headers = getallheaders();
            $authorization = null;

            // Verifica se há token de autorização
            if (isset($headers['Authorization'])) {
                $authorization = $headers['Authorization'];
            } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authorization = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_REQUEST['jwt'])) {
                $authorization = 'Bearer ' . $_REQUEST['jwt'];
            }

            // Se há token, valida apenas se for necessário para operações administrativas
            if ($authorization && !in_array($authorization, \src\Config::TOKEN_JV)) {
                $authHeaderParts = explode(' ', $authorization);
                if (count($authHeaderParts) === 2) {
                    $token = $authHeaderParts[1];
                    $check = new \src\handlers\UserHandlers();
                    if (!$check->checkLogin()) {
                        // Para leads, continua sem erro; para admin, precisa validar
                        $isAdminRequest = isset($_REQUEST['admin']) && $_REQUEST['admin'] === 'true';
                        if ($isAdminRequest) {
                            ctrl::response('Token inválido para operação administrativa', 401);
                            return;
                        }
                    }
                }
            }

            $data = Help::getInfoSiteParams($args['idempresa']);
            ctrl::response($data, 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }


    /**
     * edita parametros do site. 
     */
    public function editInfosPedidoOn()
    {
        try {
            $body = ctrl::getBody();
            if (empty($body)) {
                throw new Exception('Body não pode ser vazio');
            }
            $data = Help::editInfosPedidoOn($body);
            ctrl::response($data, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * get ingos cliente por celular
     */
    public function getInfosCliente($agrs)
    {
        try {
            if (empty($agrs['numero']) || empty($agrs['idempresa'])) {
                throw new Exception('numero/idempresa não pode ser vazio');
            }
            $data = Help::getInfosCliente($agrs['numero'], $agrs['idempresa']);
            ctrl::response($data, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }


    /**
     * rotina para adicionar cupons ao clientes eleitos nas regras de cupons
     */
    public function validarCupon($agrs)
    {
        try {
            $tokenFIx = 'rotinaCupon2024JVtech2131243227';

            if (empty($agrs['token'])) {
                throw new Exception('token não pode ser vazio'); // Use "throw" para lançar a exceção
            }

            if ($tokenFIx != $agrs['token']) {
                throw new Exception('token invalido'); // Use "throw" para lançar a exceção
            }

            $data = Help::validarCuponPendentes();
            ctrl::response($data, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    private static function validarEMP($idempresa)
    {
        if (empty($idempresa)) {
            throw new \Exception("ID da empresa não informado");
        }

        $empresa = Empresa::getEMP($idempresa);
        if (!$empresa) {
            throw new \Exception("Empresa não encontrada");
        }

        if (empty($empresa['session_myzap']) || empty($empresa['key_myzap'])) {
            throw new \Exception("Empresa não está configurada para o MyZap");
        }

        return [
            'empresa' => $empresa,
            'session_myzap' => $empresa['session_myzap'],
            'key_myzap' => $empresa['key_myzap']
        ];
    }

    public function getMyzapStatus($args)
    {
        try {
            $dado = self::validarEMP($args['idempresa']);
            $statusRaw = MyZap::getConnectionStatus($dado['session_myzap'], $dado['key_myzap']);

            ctrl::response($statusRaw, 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function getMyzapQRCode($args)
    {
        try {
            $dado = self::validarEMP($args['idempresa']);
            $res = MyZap::Start($dado['session_myzap'], $dado['key_myzap']);

            ctrl::response($res, 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function disconnectMyzap($args)
    {
        try {
            $dado = self::validarEMP($args['idempresa']);
            $res = MyZap::deleteSession($dado['session_myzap'], $dado['key_myzap']);
            ctrl::response($res, 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function sendNfePdf($args)
    {
        try {
            $idempresa      = $args['idempresa'] ?? null;
            $idpedidovenda  = $args['idpedidovenda'] ?? null;
            if (!$idempresa || !$idpedidovenda) {
                throw new \Exception('Parâmetros inválidos');
            }

            MsgMyzap::sendNfePdf($idempresa, $idpedidovenda);
            ctrl::response(['status' => 'ok'], 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function sendOrderWhatsapp($args)
    {
        try {
            $idempresa     = $args['idempresa'] ?? null;
            $idpedidovenda = $args['idpedidovenda'] ?? null;
            $body = ctrl::getBody();
            $extra = $body['obs'] ?? '';

            if (!$idempresa || !$idpedidovenda) {
                throw new \Exception('Parâmetros inválidos');
            }

            MsgMyzap::sendOrder($idempresa, $idpedidovenda, $extra);
            ctrl::response(['status' => 'ok'], 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function sendTestMessage($args)
    {
        try {
            $idempresa = $args['idempresa'] ?? null;
            if (!$idempresa) {
                throw new \Exception('ID da empresa não informado');
            }

            $body = ctrl::getBody();
            $numero = $body['numero'] ?? '';
            $mensagem = $body['mensagem'] ?? '';

            if (!$numero || !$mensagem) {
                throw new \Exception('Número e mensagem são obrigatórios');
            }

            ctrl::response(['status' => 'ok', 'message' => MsgMyzap::sendWhatsapp($idempresa, $numero, $mensagem)], 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function abreFechaSite($args)
    {
        try {
            $idempresa = $args['idempresa'] ?? null;
            if (empty($idempresa)) {
                throw new \Exception("ID da empresa não informado");
            }

            $empresa = Empresa::getInfosPedidoOn($idempresa);

            switch ($empresa['esta_aberta']) {
                case $empresa['esta_aberta'] == 'true':
                    $empresa['esta_aberta'] = 'false'; // Converte para 0
                    break;
                case $empresa['esta_aberta'] == 'false':
                    $empresa['esta_aberta'] = 'true'; // Converte para 1
                    break;
                default:
                    throw new \Exception("Estado de abertura inválido");
            }

            Empresa_parametro::update([
                'valor' =>  $empresa['esta_aberta']
            ])->where('idempresa', $idempresa)
                ->where('idparametro', 4) // 4 é o ID do parâmetro "site_ativo"
                ->execute();

            // Inverte o estado de abertura

            $data = Empresa_parametro::select()
                ->where('idempresa', $idempresa)
                ->where('idparametro', 4) // 4 é o ID do parâmetro "site_ativo"
                ->one();

            ctrl::response($data, 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function getSiteStatus($args)
    {
        try {
            $idempresa = $args['idempresa'] ?? null;
            if (empty($idempresa)) {
                throw new \Exception("ID da empresa não informado");
            }

            $aberto = Help::estaAberto($idempresa);
            ctrl::response(['aberto' => $aberto], 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function updateMyzapConfig($args)
    {
        try {
            $idempresa = $args['idempresa'] ?? null;
            $body = ctrl::getBody();
            $mensagemPadrao = $body['mensagem_padrao'] ?? null;
            $apiUrl = Ctrl::getBaseUrl() . '/api/pedido-venda-ia/' . $idempresa;

            if (!$idempresa) {
                throw new \Exception('ID da empresa é obrigatório');
            }

            if (empty($mensagemPadrao)) {
                throw new \Exception('Mensagem padrão é obrigatória');
            }

            $dado = self::validarEMP($idempresa);

            // Atualiza a configuração no MyZap
            $resultado = MyZap::updateIaConfig($dado['session_myzap'], $dado['key_myzap'], $mensagemPadrao, $apiUrl);
            if ($resultado && !isset($resultado['error'])) {
                Empresa::update([
                    'msg_padrao' => $mensagemPadrao,
                    'api_url' => $apiUrl
                ])->where('idempresa', $idempresa)->execute();
                ctrl::response(['message' => 'Configuração atualizada com sucesso'], 200);
            } else {
                throw new \Exception('Falha ao atualizar configuração no MyZap: ' . json_encode($resultado));
            }
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function getMyzapConfig($args)
    {
        try {
            $idempresa = $args['idempresa'] ?? null;

            if (!$idempresa) {
                throw new \Exception('ID da empresa é obrigatório');
            }

            $empresa = Empresa::getEMP($idempresa);
            if (!$empresa) {
                throw new \Exception('Empresa não encontrada');
            }

            ctrl::response([
                'mensagem_padrao' => $empresa['msg_padrao'] ?? '',
                'api_url' => $empresa['api_url'] ?? '',
                'id_ativa' => '0'
            ], 200);
        } catch (\Throwable $e) {
            ctrl::rejectResponse($e);
        }
    }
}

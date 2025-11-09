<?php

namespace src\controllers;

use core\Controller as ctrl;
use Exception;
use src\handlers\Sistemas as SistemasHandler;

/**
 * SistemasController - Responsável por gerenciar sistemas/clientes da API
 */
class SistemasController extends ctrl
{

    /**
     * Renderiza a página de listagem de sistemas
     * GET /sistemas
     */
    public function index()
    {
        $this->render('sistemas');
    }

    /**
     * Renderiza a página de criação de sistema
     * GET /criar-sistema
     */
    public function paginaCriar()
    {
        $this->render('sistemas/criar');
    }

    /**
     * Renderiza a página de edição de sistema
     * GET /editar-sistema/{idsistema}
     */
    public function paginaEditar($args = [])
    {
        try {
            $idsistema = $args['idsistema'] ?? null;

            if (!$idsistema) {
                // Redireciona para a lista se não tiver ID
                return self::redirect('/sistemas');
            }

            // Verifica se o sistema existe via handler
            $sistema = SistemasHandler::obterPorId($idsistema);
            if (!$sistema) {
                return self::redirect('/sistemas');
            }

            // Passa o sistema completo para a view (compatível com o template atual)

            $this->render('sistemas/editar', [
                'idsistema' => $idsistema,
                'sistema' => $sistema
            ]);
        } catch (Exception $e) {
            ctrl::log('Erro ao carregar página de edição: ' . $e->getMessage());
            return self::redirect('/sistemas');
        }
    }
    /**
     * Lista todos os sistemas
     * 
     * GET /listarSistemas
     * Headers: Authorization: Bearer {chave_api_admin}
     */
    public function listarSistemas()
    {
        try {
            $sistemas = SistemasHandler::listarTodos();
            ctrl::response($sistemas, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém um sistema específico
     * 
     * GET /obterSistema?idsistema={id}
     */
    public function obterSistema()
    {
        try {
            $idsistema = $_GET['idsistema'] ?? null;
            if (!$idsistema) {
                throw new Exception('ID do sistema não fornecido');
            }

            $sistema = SistemasHandler::obterPorId($idsistema);
            if (!$sistema) {
                throw new Exception('Sistema não encontrado');
            }

            ctrl::response($sistema, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Cria um novo sistema
     * 
     * POST /criarSistema
     * Headers: Authorization: Bearer {chave_api_admin}
     * 
     * Body JSON:
     * {
     *   "nome": "Meu Sistema",
     *   "descricao": "Descrição do sistema",
     *   "nome_remetente": "Meu Sistema"
     * }
     */
    public function criarSistema()
    {
        try {
            $dados = ctrl::getBody();
            ctrl::verificarCamposVazios($dados, ['nome', 'nome_remetente']);

            $resultado = SistemasHandler::criarComRemetente(
                1,
                $dados['nome'],
                $dados['descricao'] ?? null,
                $dados['nome_remetente'],
                'contato@jztech.com.br'
            );

            if (!empty($resultado['sucesso'])) {
                ctrl::response([
                    'mensagem' => $resultado['mensagem'],
                    'nome' => $dados['nome'],
                    'chave_api' => $resultado['chave_api'],
                    'idsistema' => $resultado['idsistema'],
                    'aviso' => 'Guarde a chave de API em local seguro. Você não poderá vê-la novamente.'
                ], 201);
            } else {
                throw new Exception($resultado['mensagem'] ?? 'Erro ao criar sistema');
            }
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Atualiza um sistema
     * 
     * PUT /atualizarSistema
     * Headers: Authorization: Bearer {chave_api_admin}
     * 
     * Body JSON:
     * {
     *   "idsistema": 1,
     *   "nome": "Novo Nome",
     *   "descricao": "Nova descrição",
     *   "nome_remetente": "Novo Nome Remetente",
     *   "ativo": true
     * }
     */
    public function atualizarSistema($idsistema)
    {
        try {
            $dados = ctrl::getBody();

            if (!$idsistema) {
                throw new Exception('ID do sistema não fornecido');
            }

            // Verificar se sistema existe
            $sistema = SistemasHandler::obterPorId($idsistema);
            if (!$sistema) {
                throw new Exception('Sistema não encontrado');
            }

            // Preparar dados para atualização
            $dadosAtualizar = [];
            if (isset($dados['nome'])) {
                $dadosAtualizar['nome'] = $dados['nome'];
            }
            if (isset($dados['descricao'])) {
                $dadosAtualizar['descricao'] = $dados['descricao'];
            }
            if (isset($dados['nome_remetente'])) {
                $dadosAtualizar['nome_remetente'] = $dados['nome_remetente'];
            }
            if (isset($dados['ativo'])) {
                $dadosAtualizar['ativo'] = $dados['ativo'] ? 1 : 0;
            }

            // Atualizar
            // Usa handler atualizar para manter regra de negócio
            $resultado = SistemasHandler::atualizar(
                $idsistema,
                1,
                $dadosAtualizar['nome'] ?? $sistema['nome'],
                $dadosAtualizar['descricao'] ?? ($sistema['descricao'] ?? '')
            );

            if (empty($resultado['sucesso'])) {
                throw new Exception($resultado['mensagem'] ?? 'Erro ao atualizar sistema');
            }

            ctrl::response([
                'mensagem' => 'Sistema atualizado com sucesso'
            ], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta um sistema (soft delete)
     * 
     * DELETE /deletarSistema?idsistema={id}
     * Headers: Authorization: Bearer {chave_api_admin}
     */
    public function deletarSistema($idsistema)
    {
        try {
            if (!$idsistema) {
                throw new Exception('ID do sistema não fornecido');
            }

            // Verificar se sistema existe
            $sistema = SistemasHandler::obterPorId($idsistema);
            if (!$sistema) {
                throw new Exception('Sistema não encontrado');
            }

            // Desativar sistema
            $del = SistemasHandler::desativarSemUsuario($idsistema);
            if (empty($del['sucesso'])) {
                throw new Exception($del['mensagem'] ?? 'Erro ao deletar sistema');
            }

            ctrl::response([
                'mensagem' => 'Sistema deletado com sucesso'
            ], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Gera uma nova chave de API para um sistema
     * 
     * POST /regenerarChaveApi
     * Headers: Authorization: Bearer {chave_api_admin}
     * 
     * Body JSON:
     * {
     *   "idsistema": 1
     * }
     */
    public function regenerarChaveApi($idsistema)
    {
        try {
            if (!$idsistema) {
                throw new Exception('ID do sistema não fornecido');
            }

            $dados = ctrl::getBody();

            // Verificar se sistema existe
            $sistema = SistemasHandler::obterPorId($idsistema);
            if (!$sistema) {
                throw new Exception('Sistema não encontrado');
            }

            // Gerar nova chave
            $novaChave = SistemasHandler::regenerarChaveApiSemUsuario($idsistema);

            if (!empty($novaChave['sucesso'])) {
                ctrl::response([
                    'mensagem' => $novaChave['mensagem'],
                    'chave_api' => $novaChave['chave_api'],
                    'aviso' => 'A chave anterior não funcionará mais. Atualize sua integração com a nova chave.'
                ], 200);
            } else {
                throw new Exception($novaChave['mensagem'] ?? 'Erro ao regenerar chave');
            }
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}

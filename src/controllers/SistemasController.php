<?php

namespace src\controllers;

use core\Controller as ctrl;
use Exception;
use src\models\Sistemas as SistemasModel;

/**
 * SistemasController - Responsável por gerenciar sistemas/clientes da API
 */
class SistemasController extends ctrl
{
    /**
     * Lista todos os sistemas
     * 
     * GET /listarSistemas
     * Headers: Authorization: Bearer {chave_api_admin}
     */
    public function listarSistemas()
    {
        try {
            // Validar autenticação (para admin)
            $this->validarAutenticacao();

            $sistemas = SistemasModel::getAll();
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
            $this->validarAutenticacao();

            $idsistema = $_GET['idsistema'] ?? null;
            if (!$idsistema) {
                throw new Exception('ID do sistema não fornecido');
            }

            $sistema = SistemasModel::getById($idsistema);
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
            $this->validarAutenticacao();

            $dados = ctrl::getBody();
            ctrl::verificarCamposVazios($dados, ['nome', 'nome_remetente']);

            // Gerar chave de API única
            $chaveApi = SistemasModel::gerarChaveApi();

            // Preparar dados
            $dadosSistema = [
                'idusuario' => 1, // Admin padrão
                'nome' => $dados['nome'],
                'descricao' => $dados['descricao'] ?? null,
                'nome_remetente' => $dados['nome_remetente'],
                'email_remetente' => 'contato@jztech.com.br',
                'chave_api' => $chaveApi
            ];

            // Criar sistema
            SistemasModel::criar($dadosSistema);

            ctrl::response([
                'mensagem' => 'Sistema criado com sucesso',
                'nome' => $dados['nome'],
                'chave_api' => $chaveApi,
                'aviso' => 'Guarde a chave de API em local seguro. Você não poderá vê-la novamente.'
            ], 201);

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
    public function atualizarSistema()
    {
        try {
            $this->validarAutenticacao();

            $dados = ctrl::getBody();
            ctrl::verificarCamposVazios($dados, ['idsistema']);

            $idsistema = $dados['idsistema'];

            // Verificar se sistema existe
            $sistema = SistemasModel::getById($idsistema);
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
            SistemasModel::atualizar($idsistema, $dadosAtualizar);

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
    public function deletarSistema()
    {
        try {
            $this->validarAutenticacao();

            $idsistema = $_GET['idsistema'] ?? null;
            if (!$idsistema) {
                throw new Exception('ID do sistema não fornecido');
            }

            // Verificar se sistema existe
            $sistema = SistemasModel::getById($idsistema);
            if (!$sistema) {
                throw new Exception('Sistema não encontrado');
            }

            // Desativar sistema
            SistemasModel::desativar($idsistema);

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
    public function regenerarChaveApi()
    {
        try {
            $this->validarAutenticacao();

            $dados = ctrl::getBody();
            ctrl::verificarCamposVazios($dados, ['idsistema']);

            $idsistema = $dados['idsistema'];

            // Verificar se sistema existe
            $sistema = SistemasModel::getById($idsistema);
            if (!$sistema) {
                throw new Exception('Sistema não encontrado');
            }

            // Gerar nova chave
            $novaChave = SistemasModel::regenerarChaveApi($idsistema);

            ctrl::response([
                'mensagem' => 'Chave de API regenerada com sucesso',
                'chave_api' => $novaChave,
                'aviso' => 'A chave anterior não funcionará mais. Atualize sua integração com a nova chave.'
            ], 200);

        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

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
        $this->render('criar_sistema');
    }

    /**
     * Renderiza a página de edição de sistema
     * GET /editar-sistema/{idsistema}
     */
    public function paginaEditar()
    {
        $idsistema = $_GET['idsistema'] ?? null;
        if (!$idsistema) {
            $this->redirect('/sistemas');
        }
        $this->render('editar_sistema');
    }

    /**
     * Valida a autenticação do usuário
     * Por enquanto, apenas verifica se há uma sessão ativa
     * Você pode implementar validação mais robusta aqui
     */
    private function validarAutenticacao()
    {
        // Verificar se há sessão ativa
        if (!isset($_SESSION['empresa']) || empty($_SESSION['empresa'])) {
            throw new Exception('Você precisa estar logado para acessar este recurso');
        }

        // Você pode adicionar validação de permissões aqui
        // Por exemplo, verificar se o usuário é admin
    }
}

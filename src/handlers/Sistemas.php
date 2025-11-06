<?php

namespace src\handlers;

use src\models\Sistemas as SistemasModel;

/**
 * Handler para lógica de negócio de Sistemas
 * Gerencia CRUD e operações relacionadas a sistemas
 *
 * @author MailJZTech
 * @date 2025-01-01
 */
class Sistemas
{
    private $model;

    public function __construct()
    {
        $this->model = new SistemasModel();
    }

    /**
     * Cria um novo sistema
     *
     * @param int $idusuario ID do usuário
     * @param string $nome Nome do sistema
     * @param string $descricao Descrição do sistema
     * @return array Retorna resultado da operação
     */
    public function criar($idusuario, $nome, $descricao = '')
    {
        // Validações
        if (empty($nome)) {
            return ['sucesso' => false, 'mensagem' => 'Nome do sistema é obrigatório'];
        }

        if (strlen($nome) < 3) {
            return ['sucesso' => false, 'mensagem' => 'Nome deve ter pelo menos 3 caracteres'];
        }

        // Gera chave de API única
        $chaveApi = $this->gerarChaveApi();

        // Cria o sistema
        $dados = [
            'idusuario' => $idusuario,
            'nome' => $nome,
            'descricao' => $descricao,
            'chave_api' => $chaveApi,
            'status' => 'ativo'
        ];

        $idsistema = $this->model->criar($dados);

        if ($idsistema) {
            return [
                'sucesso' => true,
                'mensagem' => 'Sistema criado com sucesso',
                'idsistema' => $idsistema,
                'chave_api' => $chaveApi
            ];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao criar sistema'];
    }

    /**
     * Atualiza um sistema
     *
     * @param int $idsistema ID do sistema
     * @param int $idusuario ID do usuário (para validação)
     * @param string $nome Novo nome
     * @param string $descricao Nova descrição
     * @return array Retorna resultado da operação
     */
    public function atualizar($idsistema, $idusuario, $nome, $descricao = '')
    {
        // Verifica se o sistema pertence ao usuário
        $sistema = $this->model->getById($idsistema);
        if (!$sistema || $sistema['idusuario'] != $idusuario) {
            return ['sucesso' => false, 'mensagem' => 'Sistema não encontrado'];
        }

        // Validações
        if (empty($nome)) {
            return ['sucesso' => false, 'mensagem' => 'Nome do sistema é obrigatório'];
        }

        $dados = [
            'nome' => $nome,
            'descricao' => $descricao
        ];

        $resultado = $this->model->atualizar($idsistema, $dados);

        if ($resultado) {
            return ['sucesso' => true, 'mensagem' => 'Sistema atualizado com sucesso'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar sistema'];
    }

    /**
     * Deleta um sistema (soft delete)
     *
     * @param int $idsistema ID do sistema
     * @param int $idusuario ID do usuário (para validação)
     * @return array Retorna resultado da operação
     */
    public function deletar($idsistema, $idusuario)
    {
        // Verifica se o sistema pertence ao usuário
        $sistema = $this->model->getById($idsistema);
        if (!$sistema || $sistema['idusuario'] != $idusuario) {
            return ['sucesso' => false, 'mensagem' => 'Sistema não encontrado'];
        }

        $resultado = $this->model->deletar($idsistema);

        if ($resultado) {
            return ['sucesso' => true, 'mensagem' => 'Sistema deletado com sucesso'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar sistema'];
    }

    /**
     * Regenera a chave de API de um sistema
     *
     * @param int $idsistema ID do sistema
     * @param int $idusuario ID do usuário (para validação)
     * @return array Retorna resultado da operação
     */
    public function regenerarChaveApi($idsistema, $idusuario)
    {
        // Verifica se o sistema pertence ao usuário
        $sistema = $this->model->getById($idsistema);
        if (!$sistema || $sistema['idusuario'] != $idusuario) {
            return ['sucesso' => false, 'mensagem' => 'Sistema não encontrado'];
        }

        $novaChave = $this->gerarChaveApi();
        $resultado = $this->model->atualizar($idsistema, ['chave_api' => $novaChave]);

        if ($resultado) {
            return [
                'sucesso' => true,
                'mensagem' => 'Chave de API regenerada com sucesso',
                'chave_api' => $novaChave
            ];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao regenerar chave'];
    }

    /**
     * Obtém um sistema específico
     *
     * @param int $idsistema ID do sistema
     * @param int $idusuario ID do usuário (para validação)
     * @return array|false Retorna os dados do sistema
     */
    public function obter($idsistema, $idusuario)
    {
        $sistema = $this->model->getById($idsistema);

        if (!$sistema || $sistema['idusuario'] != $idusuario) {
            return false;
        }

        return $sistema;
    }

    /**
     * Lista todos os sistemas de um usuário
     *
     * @param int $idusuario ID do usuário
     * @return array Retorna um array com os sistemas
     */
    public function listar($idusuario)
    {
        return $this->model->getByUsuario($idusuario);
    }

    /**
     * Valida uma chave de API
     *
     * @param string $chaveApi Chave de API
     * @return array|false Retorna os dados do sistema se válido
     */
    public function validarChaveApi($chaveApi)
    {
        return $this->model->getByApiKey($chaveApi);
    }

    /**
     * Gera uma chave de API única
     *
     * @return string Retorna a chave de API gerada
     */
    private function gerarChaveApi()
    {
        return 'sk_' . bin2hex(random_bytes(32));
    }
}

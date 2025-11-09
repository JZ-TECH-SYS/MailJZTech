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
    /**
     * Cria um novo sistema
     *
     * @param int $idusuario ID do usuário
     * @param string $nome Nome do sistema
     * @param string $descricao Descrição do sistema
     * @return array Retorna resultado da operação
     */
    public static function criar($idusuario, $nome, $descricao = '')
    {
        // Validações
        if (empty($nome)) {
            return ['sucesso' => false, 'mensagem' => 'Nome do sistema é obrigatório'];
        }

        if (strlen($nome) < 3) {
            return ['sucesso' => false, 'mensagem' => 'Nome deve ter pelo menos 3 caracteres'];
        }

        // Gera chave de API única
        $chaveApi = self::gerarChaveApi();

        // Cria o sistema
        $dados = [
            'idusuario' => $idusuario,
            'nome' => $nome,
            'descricao' => $descricao,
            'chave_api' => $chaveApi,
            'status' => 'ativo'
        ];

        $idsistema = SistemasModel::criar($dados);

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
     * Cria um novo sistema com remetente informado (compatível com controller atual)
     *
     * @param int $idusuario
     * @param string $nome
     * @param string|null $descricao
     * @param string $nomeRemetente
     * @param string|null $emailRemetente
     * @return array
     */
    public static function criarComRemetente($idusuario, $nome, $descricao, $nomeRemetente, $emailRemetente = null)
    {
        if (empty($nome) || empty($nomeRemetente)) {
            return ['sucesso' => false, 'mensagem' => 'Nome e nome do remetente são obrigatórios'];
        }

        $chaveApi = self::gerarChaveApi();

        $dados = [
            'idusuario' => $idusuario,
            'nome' => $nome,
            'descricao' => $descricao ?? '',
            'chave_api' => $chaveApi,
            'status' => 'ativo',
            'nome_remetente' => $nomeRemetente,
            'email_remetente' => $emailRemetente ?: 'contato@jztech.com.br'
        ];

        $idsistema = SistemasModel::criar($dados);

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
    public static function atualizar($idsistema, $idusuario, $nome, $descricao = '')
    {
        // Verifica se o sistema pertence ao usuário
        $sistema = SistemasModel::getById($idsistema);
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

        $resultado = SistemasModel::atualizar($idsistema, $dados);

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
    public static function deletar($idsistema, $idusuario)
    {
        // Verifica se o sistema pertence ao usuário
        $sistema = SistemasModel::getById($idsistema);
        if (!$sistema || $sistema['idusuario'] != $idusuario) {
            return ['sucesso' => false, 'mensagem' => 'Sistema não encontrado'];
        }

        $resultado = SistemasModel::desativar($idsistema);

        if ($resultado) {
            return ['sucesso' => true, 'mensagem' => 'Sistema deletado com sucesso'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar sistema'];
    }

    /**
     * Desativa um sistema sem validar usuário (uso administrativo)
     */
    public static function desativarSemUsuario($idsistema)
    {
        $resultado = SistemasModel::desativar($idsistema);
        return $resultado ? ['sucesso' => true, 'mensagem' => 'Sistema deletado com sucesso'] : ['sucesso' => false, 'mensagem' => 'Erro ao deletar sistema'];
    }

    /**
     * Regenera a chave de API de um sistema
     *
     * @param int $idsistema ID do sistema
     * @param int $idusuario ID do usuário (para validação)
     * @return array Retorna resultado da operação
     */
    public static function regenerarChaveApi($idsistema, $idusuario)
    {
        // Verifica se o sistema pertence ao usuário
        $sistema = SistemasModel::getById($idsistema);
        if (!$sistema || $sistema['idusuario'] != $idusuario) {
            return ['sucesso' => false, 'mensagem' => 'Sistema não encontrado'];
        }

        $novaChave = SistemasModel::regenerarChaveApi($idsistema);

        if ($novaChave) {
            return [
                'sucesso' => true,
                'mensagem' => 'Chave de API regenerada com sucesso',
                'chave_api' => $novaChave
            ];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao regenerar chave'];
    }

    /**
     * Regenera a chave de API sem validar usuário (uso administrativo)
     */
    public static function regenerarChaveApiSemUsuario($idsistema)
    {
        $novaChave = SistemasModel::regenerarChaveApi($idsistema);
        if ($novaChave) {
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
    public static function obter($idsistema, $idusuario)
    {
        $sistema = SistemasModel::getById($idsistema);

        if (!$sistema || $sistema['idusuario'] != $idusuario) {
            return false;
        }

        return $sistema;
    }

    /**
     * Obtém sistema apenas por ID (sem validação de usuário)
     */
    public static function obterPorId($idsistema)
    {
        return SistemasModel::getById($idsistema);
    }

    /**
     * Lista todos os sistemas de um usuário
     *
     * @param int $idusuario ID do usuário
     * @return array Retorna um array com os sistemas
     */
    public static function listar($idusuario)
    {
        return SistemasModel::getByUsuario($idusuario);
    }

    /**
     * Lista todos os sistemas ativos (uso administrativo)
     */
    public static function listarTodos()
    {
        return SistemasModel::getAll();
    }

    /**
     * Valida uma chave de API
     *
     * @param string $chaveApi Chave de API
     * @return array|false Retorna os dados do sistema se válido
     */
    public static function validarChaveApi($chaveApi)
    {
        return SistemasModel::getByApiKey($chaveApi);
    }

    /**
     * Verifica se um idsistema existe (ativo/inativo) — usado por controllers para validação leve
     */
    public static function existeId($idsistema)
    {
        return (bool) SistemasModel::getById($idsistema);
    }

    /**
     * Gera uma chave de API única
     *
     * @return string Retorna a chave de API gerada
     */
    private static function gerarChaveApi()
    {
        return 'sk_' . bin2hex(random_bytes(32));
    }
}

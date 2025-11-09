<?php

namespace src\handlers;

use src\models\Backup_banco_config;
use src\models\Backup_execucao_log;
use Exception;

/**
 * Handler para gerenciar configurações de backup de bancos de dados.
 * Implementa regras de negócio para CRUD de configurações.
 */
class BackupConfig
{
    /**
     * Lista todas as configurações de backup.
     *
     * @param bool|null $apenasAtivos
     * @return array
     */
    public static function listarTodos(?bool $apenasAtivos = null): array
    {
        if ($apenasAtivos === true) {
            return Backup_banco_config::obterAtivos();
        }

        return Backup_banco_config::select()
            ->orderBy('nome_banco', 'ASC')
            ->get();
    }

    /**
     * Busca uma configuração por ID.
     *
     * @param int $id
     * @return array
     * @throws Exception
     */
    public static function obterPorId(int $id): array
    {
        $config = Backup_banco_config::select()
            ->where('idbackup_banco_config', $id)
            ->one();

        if (empty($config)) {
            throw new Exception("Configuração de backup não encontrada (ID: {$id})");
        }

        return $config;
    }

    /**
     * Verifica se existe uma configuração com o ID informado.
     *
     * @param int $id
     * @return bool
     */
    public static function existeId(int $id): bool
    {
        $config = Backup_banco_config::select(['idbackup_banco_config'])
            ->where('idbackup_banco_config', $id)
            ->one();

        return !empty($config);
    }

    /**
     * Cria uma nova configuração de backup.
     *
     * @param array $dados
     * @return int ID da configuração criada
     * @throws Exception
     */
    public static function criar(array $dados): int
    {
        // Validações
        self::validarDados($dados);

        // Verifica se já existe configuração com esse nome
        if (Backup_banco_config::existeNome($dados['nome_banco'])) {
            throw new Exception("Já existe uma configuração para o banco '{$dados['nome_banco']}'");
        }

        // Prepara dados para inserção
        $inserir = [
            'nome_banco' => trim($dados['nome_banco']),
            'bucket_nome' => trim($dados['bucket_nome'] ?? 'dbjztech'),
            'pasta_base' => trim($dados['pasta_base']),
            'retencao_dias' => (int)($dados['retencao_dias'] ?? 5),
            'ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1
        ];

        $id = Backup_banco_config::insert($inserir)->execute();

        if (!$id) {
            throw new Exception("Erro ao criar configuração de backup");
        }

        return $id;
    }

    /**
     * Atualiza uma configuração existente.
     *
     * @param int $id
     * @param array $dados
     * @return void
     * @throws Exception
     */
    public static function atualizar(int $id, array $dados): void
    {
        // Verifica se existe
        if (!self::existeId($id)) {
            throw new Exception("Configuração não encontrada (ID: {$id})");
        }

        // Validações
        self::validarDados($dados, $id);

        // Verifica se está tentando mudar o nome para um já existente
        if (isset($dados['nome_banco']) && Backup_banco_config::existeNome($dados['nome_banco'], $id)) {
            throw new Exception("Já existe uma configuração para o banco '{$dados['nome_banco']}'");
        }

        // Prepara dados para atualização
        $atualizar = [];

        if (isset($dados['nome_banco'])) {
            $atualizar['nome_banco'] = trim($dados['nome_banco']);
        }
        if (isset($dados['bucket_nome'])) {
            $atualizar['bucket_nome'] = trim($dados['bucket_nome']);
        }
        if (isset($dados['pasta_base'])) {
            $atualizar['pasta_base'] = trim($dados['pasta_base']);
        }
        if (isset($dados['retencao_dias'])) {
            $atualizar['retencao_dias'] = (int)$dados['retencao_dias'];
        }
        if (isset($dados['ativo'])) {
            $atualizar['ativo'] = (int)$dados['ativo'];
        }

        if (empty($atualizar)) {
            throw new Exception("Nenhum dado para atualizar");
        }

        Backup_banco_config::update($atualizar)
            ->where('idbackup_banco_config', $id)
            ->execute();
    }

    /**
     * Exclui uma configuração de backup.
     * Remove também todos os logs associados (CASCADE).
     *
     * @param int $id
     * @return void
     * @throws Exception
     */
    public static function excluir(int $id): void
    {
        if (!self::existeId($id)) {
            throw new Exception("Configuração não encontrada (ID: {$id})");
        }

        Backup_banco_config::delete()
            ->where('idbackup_banco_config', $id)
            ->execute();
    }

    /**
     * Atualiza estatísticas de backup (chamado após execução bem-sucedida).
     *
     * @param int $id
     * @return void
     * @throws Exception
     */
    public static function atualizarEstatisticas(int $id): void
    {
        if (!self::existeId($id)) {
            throw new Exception("Configuração não encontrada (ID: {$id})");
        }

        $dataAtual = date('Y-m-d H:i:s');
        Backup_banco_config::atualizarEstatisticas($id, $dataAtual);
    }

    /**
     * Valida os dados de entrada.
     *
     * @param array $dados
     * @param int|null $idAtual (para updates)
     * @return void
     * @throws Exception
     */
    private static function validarDados(array $dados, ?int $idAtual = null): void
    {
        // Validação para criação
        if ($idAtual === null) {
            if (empty($dados['nome_banco'])) {
                throw new Exception("O campo 'nome_banco' é obrigatório");
            }
            if (empty($dados['pasta_base'])) {
                throw new Exception("O campo 'pasta_base' é obrigatório");
            }
        }

        // Validações comuns
        if (isset($dados['nome_banco']) && empty(trim($dados['nome_banco']))) {
            throw new Exception("O campo 'nome_banco' não pode ser vazio");
        }

        if (isset($dados['pasta_base']) && empty(trim($dados['pasta_base']))) {
            throw new Exception("O campo 'pasta_base' não pode ser vazio");
        }

        if (isset($dados['bucket_nome']) && empty(trim($dados['bucket_nome']))) {
            throw new Exception("O campo 'bucket_nome' não pode ser vazio");
        }

        if (isset($dados['retencao_dias'])) {
            $retencao = (int)$dados['retencao_dias'];
            if ($retencao < 1) {
                throw new Exception("O campo 'retencao_dias' deve ser maior ou igual a 1");
            }
        }

        // Validação de caracteres perigosos no nome do banco (segurança)
        if (isset($dados['nome_banco'])) {
            $nome = $dados['nome_banco'];
            if (preg_match('/[^a-zA-Z0-9_\-]/', $nome)) {
                throw new Exception("O campo 'nome_banco' contém caracteres inválidos. Use apenas letras, números, hífen e underscore");
            }
        }
    }
}

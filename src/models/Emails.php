<?php

namespace src\models;

use core\Model;
use core\Database;

/**
 * Classe modelo para a tabela 'emails_enviados' do banco de dados.
 * Representa o histórico de e-mails enviados através da API.
 *
 * @author MailJZTech
 * @date 2025-01-01
 */
class Emails extends Model
{
    /**
     * Obtém todos os e-mails de um sistema com paginação
     *
     * @param int $idsistema ID do sistema
     * @param int $limite Limite de registros
     * @param int $offset Offset para paginação
     * @return array Retorna um array com os e-mails
     */
    public function getBySystem($idsistema, $limite = 50, $offset = 0)
    {
        return self::select(['*'])
            ->where('idsistema', $idsistema)
            ->orderBy('data_criacao', 'DESC')
            ->limit($limite)
            ->offset($offset)
            ->get();
    }

    /**
     * Obtém um e-mail específico pelo ID
     *
     * @param int $idemail ID do e-mail
     * @return array|false Retorna os dados do e-mail
     */
    public function getById($idemail)
    {
        return self::select(['*'])
            ->where('idemail', $idemail)
            ->one();
    }

    /**
     * Cria um novo registro de e-mail
     *
     * @param array $dados Dados do e-mail
     * @return int|false Retorna o ID do e-mail criado
     */
    public function criar($dados)
    {
        return self::insert([
            'idsistema' => $dados['idsistema'],
            'idusuario' => $dados['idusuario'],
            'destinatario' => $dados['destinatario'],
            'cc' => $dados['cc'] ?? null,
            'bcc' => $dados['bcc'] ?? null,
            'assunto' => $dados['assunto'],
            'corpo_html' => $dados['corpo_html'],
            'corpo_texto' => $dados['corpo_texto'] ?? null,
            'anexos' => $dados['anexos'] ?? null,
            'status' => $dados['status'] ?? 'pendente'
        ])->execute();
    }

    /**
     * Atualiza o status de um e-mail
     *
     * @param int $idemail ID do e-mail
     * @param string $status Novo status
     * @param string|null $mensagemErro Mensagem de erro (se houver)
     * @return bool Retorna true se atualizado com sucesso
     */
    public function atualizarStatus($idemail, $status, $mensagemErro = null)
    {
        $dados = [
            'status' => $status,
            'mensagem_erro' => $mensagemErro,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ];

        if ($status === 'enviado') {
            $dados['data_envio'] = date('Y-m-d H:i:s');
        }

        return self::update($dados)
            ->where('idemail', $idemail)
            ->execute();
    }

    /**
     * Conta total de e-mails de um sistema
     *
     * @param int $idsistema ID do sistema
     * @return int Retorna o total de e-mails
     */
    public function contarPorSistema($idsistema)
    {
        $result = self::select(['*'])
            ->where('idsistema', $idsistema)
            ->count();
        
        return $result ?? 0;
    }

    /**
     * Obtém estatísticas de e-mails de um sistema
     * Usa SQL puro via switchParams()
     *
     * @param int $idsistema ID do sistema
     * @return array|false Retorna as estatísticas
     */
    public function obterEstatisticas($idsistema)
    {
        $params = [
            'idsistema' => $idsistema
        ];
        
        $resultado = Database::switchParams($params, 'emails_obter_estatisticas', true);
        
        return !empty($resultado['retorno']) ? $resultado['retorno'][0] : false;
    }

    /**
     * Deleta um e-mail (soft delete)
     *
     * @param int $idemail ID do e-mail
     * @return bool Retorna true se deletado com sucesso
     */
    public function deletar($idemail)
    {
        return self::update(['status' => 'deletado'])
            ->where('idemail', $idemail)
            ->execute();
    }
}

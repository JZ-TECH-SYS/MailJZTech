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
class Emails_enviados extends Model
{
    /**
     * Obtém todos os e-mails de um sistema com paginação
     *
     * @param int $idsistema ID do sistema
     * @param int $limite Limite de registros
     * @param int $offset Offset para paginação
     * @return array Retorna um array com os e-mails
     */
    public static function getBySystem($idsistema, $limite = 50, $offset = 0)
    {
        $query = self::select()
            ->orderBy('data_criacao', 'DESC')
            ->limit($limite)
            ->offset($offset);

        if (!empty($idsistema)) {
            $query->where('idsistema', $idsistema);
        }

        return $query->get();
    }

    /**
     * Obtém um e-mail específico pelo ID
     *
     * @param int $idemail ID do e-mail
     * @return array|false Retorna os dados do e-mail
     */
    public static function getById($idemail)
    {
        return self::select()
            ->where('idemail', $idemail)
            ->one();
    }

    /**
     * Cria um novo registro de e-mail
     *
     * @param array $dados Dados do e-mail
     * @return int|false Retorna o ID do e-mail criado
     */
    public static function criar($dados)
    {
        $payload = [
            'idsistema' => $dados['idsistema'],
            'idusuario' => $dados['idusuario'] ?? null,
            'destinatario' => $dados['destinatario'],
            'cc' => $dados['cc'] ?? null,
            'bcc' => $dados['bcc'] ?? null,
            'assunto' => $dados['assunto'],
            'corpo_html' => $dados['corpo_html'] ?? null,
            'corpo_texto' => $dados['corpo_texto'] ?? null,
            'anexos' => $dados['anexos'] ?? null,
            'status' => $dados['status'] ?? 'pendente',
            'smtp_code' => $dados['smtp_code'] ?? null,
            'smtp_response' => $dados['smtp_response'] ?? null,
            'tamanho_bytes' => $dados['tamanho_bytes'] ?? null,
            'mensagem_erro' => $dados['mensagem_erro'] ?? null,
            'tentativas' => $dados['tentativas'] ?? 1
        ];

        if (!empty($dados['data_envio'])) {
            $payload['data_envio'] = $dados['data_envio'];
        }

        return self::insert($payload)->execute();
    }

    /**
     * Atualiza o status de um e-mail
     *
     * @param int $idemail ID do e-mail
     * @param string $status Novo status
     * @param string|null $mensagemErro Mensagem de erro (se houver)
     * @param string|null $smtpCode Código SMTP
     * @param string|null $smtpResponse Resposta SMTP
     * @return bool Retorna true se atualizado com sucesso
     */
    public static function atualizarStatus(
        $idemail, 
        $status, 
        $mensagemErro = null,
        $smtpCode = null,
        $smtpResponse = null
    ) {
        $dados = [
            'status' => $status,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ];

        if ($mensagemErro !== null) {
            $dados['mensagem_erro'] = $mensagemErro;
        }
        
        if ($smtpCode !== null) {
            $dados['smtp_code'] = $smtpCode;
        }
        
        if ($smtpResponse !== null) {
            $dados['smtp_response'] = $smtpResponse;
        }

        if ($status === 'enviado' || $status === 'aceito') {
            $dados['data_envio'] = date('Y-m-d H:i:s');
        }

        return self::update($dados)
            ->where('idemail', $idemail)
            ->execute();
    }
    
    /**
     * Incrementa o contador de tentativas
     *
     * @param int $idemail ID do e-mail
     * @return bool
     */
    public static function incrementarTentativas($idemail)
    {
        // Usar SQL direto para incrementar
        $email = self::getById($idemail);
        if (!$email) {
            return false;
        }
        
        return self::update([
            'tentativas' => ($email['tentativas'] ?? 0) + 1,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ])->where('idemail', $idemail)->execute();
    }
    
    /**
     * Obtém e-mails pendentes para reprocessamento
     *
     * @param int $limite Limite de registros
     * @param int $maxTentativas Máximo de tentativas
     * @return array Lista de e-mails pendentes
     */
    public static function obterPendentes($limite = 50, $maxTentativas = 3)
    {
        return self::select()
            ->where('status', 'IN', ['pendente', 'falha'])
            ->where('tentativas', '<', $maxTentativas)
            ->orderBy('data_criacao', 'ASC')
            ->limit($limite)
            ->get();
    }
    
    /**
     * Obtém e-mails por status
     *
     * @param string|array $status Status ou lista de status
     * @param int $limite Limite de registros
     * @return array Lista de e-mails
     */
    public static function obterPorStatus($status, $limite = 50)
    {
        $query = self::select()
            ->orderBy('data_criacao', 'DESC')
            ->limit($limite);
            
        if (is_array($status)) {
            $query->where('status', 'IN', $status);
        } else {
            $query->where('status', $status);
        }
        
        return $query->get();
    }

    /**
     * Conta total de e-mails de um sistema (ou todos se null)
     *
     * @param int|null $idsistema ID do sistema (null = todos)
     * @return int Retorna o total de e-mails
     */
    public static function countBySystem($idsistema = null)
    {
        $query = self::select();
        
        if (!empty($idsistema)) {
            $query->where('idsistema', $idsistema);
        }
        
        $result = $query->count();
        
        return $result ?? 0;
    }

    /**
     * Obtém estatísticas de e-mails de um sistema
     * Usa SQL puro via switchParams()
     *
     * @param int $idsistema ID do sistema
     * @return array|false Retorna as estatísticas
     */
    public static function obterEstatisticas($idsistema)
    {
        // Quando $idsistema for null/0, enviar 0 para SQL tratar como geral
        $params = [
            'idsistema' => (!empty($idsistema) && is_numeric($idsistema)) ? (int)$idsistema : 0
        ];
        
        $resultado = Database::switchParams($params, 'emails_obter_estatisticas', true);
        
        return !empty($resultado['retorno']) ? $resultado['retorno'][0] : false;
    }

    /**
     * Obtém últimos e-mails globalmente (todos os sistemas)
     */
    public static function getRecentes($limite = 10)
    {
        return self::select(['idemail','destinatario','assunto','status','data_envio','data_criacao'])
            ->orderBy('data_criacao', 'DESC')
            ->limit($limite)
            ->get();
    }

    /**
     * Deleta um e-mail (soft delete)
     *
     * @param int $idemail ID do e-mail
     * @return bool Retorna true se deletado com sucesso
     */
    public static function deletar($idemail)
    {
        return self::update(['status' => 'deletado'])
            ->where('idemail', $idemail)
            ->execute();
    }
}

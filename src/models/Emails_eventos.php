<?php

namespace src\models;

use core\Model;

/**
 * Classe modelo para a tabela 'emails_eventos' do banco de dados.
 * Registra eventos de tracking de e-mails (bounces, entregas, aberturas, etc.)
 *
 * Tipos de evento:
 * - envio: e-mail foi enviado
 * - aceito: aceito pelo servidor SMTP
 * - rejeitado: rejeitado pelo servidor SMTP
 * - bounce: e-mail retornou (não entregue)
 * - entregue: confirmação de entrega
 * - aberto: e-mail foi aberto
 * - clique: link foi clicado
 * - spam: marcado como spam
 * - erro: erro durante processamento
 *
 * @author MailJZTech
 * @date 2025-12-14
 */
class Emails_eventos extends Model
{
    /**
     * Sobrescreve o nome da tabela
     */
    public static function getTableName()
    {
        return 'emails_eventos';
    }

    /**
     * Registra um novo evento de e-mail
     *
     * @param int $idemail ID do e-mail
     * @param string $tipo_evento Tipo do evento
     * @param string|null $codigo_smtp Código SMTP
     * @param string|null $mensagem Mensagem descritiva
     * @param array|null $dados_extras Dados adicionais
     * @param string $origem Origem do evento (smtp, webhook, manual)
     * @return int|false Retorna o ID do evento criado
     */
    public static function registrar(
        int $idemail,
        string $tipo_evento,
        ?string $codigo_smtp = null,
        ?string $mensagem = null,
        ?array $dados_extras = null,
        string $origem = 'smtp'
    ) {
        return self::insert([
            'idemail' => $idemail,
            'tipo_evento' => $tipo_evento,
            'codigo_smtp' => $codigo_smtp,
            'mensagem' => $mensagem,
            'dados_extras' => $dados_extras ? json_encode($dados_extras) : null,
            'origem' => $origem
        ])->execute();
    }

    /**
     * Obtém todos os eventos de um e-mail
     *
     * @param int $idemail ID do e-mail
     * @return array Lista de eventos
     */
    public static function obterPorEmail(int $idemail): array
    {
        return self::select()
            ->where('idemail', $idemail)
            ->orderBy('data_evento', 'ASC')
            ->get();
    }

    /**
     * Obtém o último evento de um e-mail
     *
     * @param int $idemail ID do e-mail
     * @return array|false Último evento
     */
    public static function obterUltimo(int $idemail)
    {
        return self::select()
            ->where('idemail', $idemail)
            ->orderBy('data_evento', 'DESC')
            ->limit(1)
            ->one();
    }

    /**
     * Verifica se um e-mail teve bounce
     *
     * @param int $idemail ID do e-mail
     * @return bool
     */
    public static function teveBounce(int $idemail): bool
    {
        $evento = self::select()
            ->where('idemail', $idemail)
            ->where('tipo_evento', 'bounce')
            ->one();
        
        return !empty($evento);
    }

    /**
     * Obtém eventos por tipo
     *
     * @param string $tipo_evento Tipo do evento
     * @param int $limite Limite de registros
     * @return array Lista de eventos
     */
    public static function obterPorTipo(string $tipo_evento, int $limite = 100): array
    {
        return self::select()
            ->where('tipo_evento', $tipo_evento)
            ->orderBy('data_evento', 'DESC')
            ->limit($limite)
            ->get();
    }

    /**
     * Conta eventos por tipo para um período
     *
     * @param string $tipo_evento Tipo do evento
     * @param string $dataInicio Data de início (Y-m-d)
     * @param string $dataFim Data de fim (Y-m-d)
     * @return int Total de eventos
     */
    public static function contarPorTipoPeriodo(
        string $tipo_evento,
        string $dataInicio,
        string $dataFim
    ): int {
        $result = self::select()
            ->where('tipo_evento', $tipo_evento)
            ->where('data_evento', '>=', $dataInicio . ' 00:00:00')
            ->where('data_evento', '<=', $dataFim . ' 23:59:59')
            ->count();
        
        return $result ?? 0;
    }
}

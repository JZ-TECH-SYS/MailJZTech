<?php

/**
 * EmailContabilidadeController
 *
 * Dispara XMLs de NF‑e para os e‑mails de contabilidade.
 */

namespace src\controllers;

use core\Controller as ctrl;
use src\models\Nota_fiscal;
use src\models\Nota_fiscal_envio;
use Exception;

class EmailContabilidadeController extends ctrl
{
    /** pares id_empresa / email */
    const EMPRESAS = [
        ['id_empresa' => 3, 'email_contabilidade' => 'boxj.jettax360prod.48094@jettax.com.br'],
    ];

    public function enviarPendentes(): void
    {
        try {
            $mailer = new EmailController();

            foreach (self::EMPRESAS as $emp) {
                $idEmpresa = $emp['id_empresa'];
                $destino   = $emp['email_contabilidade'];

                $notas = Nota_fiscal::getNotasPendentesEnvioContabilidade($idEmpresa);
                if (!$notas) {
                    continue;
                }

                foreach ($notas as $nf) {
                    if (empty($nf['xml'])) {
                        continue; // sem XML → pula
                    }

                    // Gera arquivo temporário .xml
                    $chave   = $nf['chavesefaz'] ?: uniqid();
                    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "{$chave}.xml";
                    file_put_contents($tmpFile, $nf['xml']);

                    $assunto  = "NF-e {$chave} | XML em anexo";
                    $htmlBody = "<p>Olá,</p><p>Segue em anexo o XML da NF‑e <strong>{$chave}</strong>.</p><p style=\"font-size:0.85em;color:#666\">Mensagem automática • Não responder</p>";
                    $altBody  = "Olá!\n\nSegue em anexo o XML da NF‑e {$chave}.\n\n— Mensagem automática, favor não responder.";

                    $ok = $mailer->sendEmail(
                        $destino,
                        $assunto,
                        $htmlBody,
                        $altBody,
                        null,
                        $tmpFile
                    );

                    unlink($tmpFile); // remove arquivo temporário

                    Nota_fiscal_envio::insert([
                        'idnotafiscal'        => $nf['idregistronota'],
                        'idempresa'           => $idEmpresa,
                        'email_contabilidade' => $destino,
                        'enviado'             => $ok ? 1 : 0,
                        'data_envio'          => date('Y-m-d H:i:s')
                    ])->execute();
                }
            }

            ctrl::response(['msg' => 'Envios concluídos'], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}

<?php



/**

 * Controlador de Impressão responsável por gerenciar operações relacionadas a Impressão.

 * Autor: Joaosn

 * Data de início: 20/08/2023

 */
namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use src\handlers\Printer;
use src\handlers\Printer2;

class PrinterController extends ctrl
{
    /**
     * pega o pedido de venda pelo id ou item do pedido e retorna PDF
     */
    public function getPrint($data)
    {
        try {
            $pedido = Printer::getPrint($data);
            if (!$pedido) {
                throw new Exception('Pedido não Localizado pedido!');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * adiciona um pedido ou item na fila de impressão!
     */
    public function sendPrint($data)
    {
        try {
            $pedido = Printer::sendPrint($data);
            if (!$pedido) {
                throw new Exception('Pedido não Localizado pedido!');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * cron para enviar os pedidos para impressão
     */
    public function cronImpressaoDireta($data)
    {

        try {
            $idempresa = $data['idempresa'] ?? $_SESSION['empresa']['idempresa'];
            $pdfs = Printer::cronImpressaoDireta($idempresa);
            if (!$pdfs) {
                throw new Exception('Pedido não Localizado pedido!');
            }
            ctrl::response($pdfs, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * cron para enviar os pedidos para impressão
     */
    public function cronImpressaoDiretav2($data)
    {

        try {
            $idempresa = $data['idempresa'] ?? $_SESSION['empresa']['idempresa'];
            $pdfs = Printer2::cronImpressaoDireta($idempresa);
            if (!$pdfs) {
                throw new Exception('Pedido não Localizado pedido!');
            }
            ctrl::response($pdfs, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * cron para enviar os pedidos para impressão COM IMPRESSORA POR CATEGORIA
     * Retorna objetos { texto, impressora } para permitir impressão direcionada
     */
    public function cronImpressaoDiretav3($data)
    {
        try {
            $idempresa = $data['idempresa'] ?? $_SESSION['empresa']['idempresa'];
            // ✅ Passa true para retornar { texto, impressora }
            $pdfs = Printer2::cronImpressaoDireta($idempresa, true);
            if (!$pdfs) {
                throw new Exception('Pedido não Localizado pedido!');
            }
            ctrl::response($pdfs, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * lista os pedidos que estão na fila de impressão
     */
    public function lista($data)
    {
        try {
            $lista = Printer::getFilaImpressao($data['idempresa']);
            ctrl::response($lista, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * marca como impresso
     */
    public function remove($data)
    {
        try {
            $lista = Printer::marcarImpresso($data);
            if (!$lista) {
                throw new Exception('Pedido não Localizado pedido!');
            }
            ctrl::response($lista, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}

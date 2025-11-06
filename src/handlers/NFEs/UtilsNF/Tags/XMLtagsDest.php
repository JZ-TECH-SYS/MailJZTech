<?php

namespace src\handlers\NFEs\UtilsNF\Tags;

class XMLtagsDest
{
    /**
     * Gera tag <dest> - Destinatário da NF-e
     */
    public static function dest(array $pedido): \stdClass
    {
        $std = new \stdClass();
        $std->xNome = $pedido['destinatario']['nome'] ?? 'CONSUMIDOR FINAL';
        $std->indIEDest = empty($pedido['destinatario']['cnpj']) ? '9' : (!empty($pedido['destinatario']['ie']) ? '1' : '2');

        if (!empty($pedido['destinatario']['cnpj'])) {
            $std->CNPJ = $pedido['destinatario']['cnpj'];
        } else {
            $std->CPF = $pedido['destinatario']['cpf'] ?? '';
        }

        $std->email = $pedido['destinatario']['email'] ?? '';

        return $std;
    }

    /**
     * Gera tag <enderDest> - Endereço do Destinatário
     */
    public static function enderDest(array $pedido): \stdClass
    {
        $std = new \stdClass();
        $std->xLgr = $pedido['destinatario']['endereco'] ?? 'Não Informado';
        $std->nro = $pedido['destinatario']['numero_casa'] ?? '0';
        $std->xBairro = $pedido['destinatario']['bairro'] ?? 'Centro';
        $std->cMun = $pedido['destinatario']['codmunicipio'] ?? '0000000';
        $std->xMun = $pedido['destinatario']['cidade'] ?? 'Cidade Desconhecida';
        $std->UF = $pedido['destinatario']['uf'] ?? 'PR';
        $std->CEP = $pedido['destinatario']['cep'] ?? '00000000';
        $std->cPais = 1058;
        $std->xPais = 'Brasil';
        $std->fone = $pedido['destinatario']['celular'] ?? '';

        return $std;
    }
}

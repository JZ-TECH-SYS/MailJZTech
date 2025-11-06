<?php

namespace src\handlers\NFEs\UtilsNF\Tags;

class XMLtagsEmit
{
    /**
     * Gera tag <emit> - Emitente da NF-e
     */
    public static function emit(array $pedido, int $codigoRegimeTributario): \stdClass
    {
        $std = new \stdClass();
        $std->xNome = $pedido['emitente']['nome'] ?? 'NOME EMITENTE';
        $std->xFant = $pedido['emitente']['nomefantasia'] ?? $std->xNome;
        $std->IE    = $pedido['emitente']['inscricao_estadual'] ?? 'ISENTO';
        $std->CRT   = $codigoRegimeTributario;
        $std->CNPJ  = $pedido['emitente']['cnpj'] ?? '';

        return $std;
    }

    /**
     * Gera tag <enderEmit> - Endereço do Emitente
     */
    public static function enderEmit(array $pedido): \stdClass
    {
        $std = new \stdClass();
        $std->xLgr = $pedido['emitente']['endereco'] ?? 'Sem Endereço';
        $std->nro = $pedido['emitente']['numero_casa'] ?? '0';
        $std->xBairro = $pedido['emitente']['bairro'] ?? 'Centro';
        $std->cMun = $pedido['emitente']['codmunicipio'] ?? '4111555'; // Exemplo: Ivaté-PR
        $std->xMun = $pedido['emitente']['cidade'] ?? 'Cidade Desconhecida';
        $std->UF = $pedido['emitente']['uf'] ?? 'PR';
        $std->CEP = $pedido['emitente']['cep'] ?? '00000000';
        $std->cPais = 1058;
        $std->xPais = 'Brasil';
        $std->fone = $pedido['emitente']['celular'] ?? '';

        return $std;
    }
}

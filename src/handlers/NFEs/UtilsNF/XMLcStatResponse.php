<?php

namespace src\handlers\NFEs\UtilsNF;

class XMLcStatResponse
{
    /** Mapeamento de códigos SEFAZ para descrição */
    private static array $descriptions = [
        100 => 'Autorizado o uso da NF-e',
        101 => 'Cancelamento de NF-e homologado',
        102 => 'Inutilização de número homologado',
        103 => 'Lote recebido com sucesso',
        104 => 'Lote processado',
        105 => 'Lote em processamento',
        106 => 'Lote não localizado',
        107 => 'Serviço em Operação',
        108 => 'Serviço Paralisado Momentaneamente (curto prazo)',
        109 => 'Serviço Paralisado sem Previsão',
        110 => 'Uso Denegado',
        111 => 'Consulta cadastro com uma ocorrência',
        112 => 'Consulta cadastro com mais de uma ocorrência',
        113 => 'SVC em processo de desativação SVC será desabilitada para a SEFAZ-XX em dd/mm/aa às hh:mm horas',
        114 => 'SVC-RS desabilitada pela SEFAZ de Origem',
        124 => 'EPEC Autorizado',
        128 => 'Lote de Evento Processado',
        135 => 'Evento registrado e vinculado a NF-e',
        136 => 'Evento registrado, mas não vinculado a NF-e',
        137 => 'Nenhum documento localizado para o Destinatário',
        138 => 'Documento localizado para o Destinatário',
        139 => 'Pedido de Download processado',
        140 => 'Download disponibilizado',
        142 => 'Ambiente de Contingência EPEC bloqueado para o Emitente',
        150 => 'Autorizado o uso da NF-e, autorização fora de prazo',
        151 => 'Cancelamento de NF-e homologado fora de prazo',
        155 => 'Cancelamento homologado fora de prazo',
        201 => 'Rejeição: Número máximo de numeração a inutilizar ultrapassou o limite',
        202 => 'Rejeição: Falha no reconhecimento da autoria ou integridade do arquivo digital',
        203 => 'Rejeição: Emissor não habilitado para emissão de NF-e',
        204 => 'Rejeição: Duplicidade de NF-e [nRec:999999999999999]',
        205 => 'Rejeição: NF-e está denegada na base de dados da SEFAZ [nRec:999999999999999]',
        206 => 'Rejeição: NF-e já está inutilizada na Base de dados da SEFAZ',
        207 => 'Rejeição: CNPJ do emitente inválido',
        208 => 'Rejeição: CNPJ do destinatário inválido',
        209 => 'Rejeição: IE do emitente inválida',
        210 => 'Rejeição: IE do destinatário inválida',
        211 => 'Rejeição: IE do substituto inválida',
        212 => 'Rejeição: Data de emissão NF-e posterior a data de recebimento',
        213 => 'Rejeição: CNPJ-Base do Emitente difere do CNPJ-Base do Certificado Digital',
        214 => 'Rejeição: Tamanho da mensagem excedeu o limite estabelecido',
        215 => 'Rejeição: Falha no schema XML',
        216 => 'Rejeição: Chave de Acesso difere da cadastrada',
        217 => 'Rejeição: NF-e não consta na base de dados da SEFAZ',
        218 => 'Rejeição: NF-e já está cancelada na base de dados da SEFAZ [nRec:999999999999999]',
        219 => 'Rejeição: Circulação da NF-e verificada',
        220 => 'Rejeição: Prazo de Cancelamento superior ao previsto na Legislação',
        221 => 'Rejeição: Confirmado o recebimento da NF-e pelo destinatário',
        222 => 'Rejeição: Protocolo de Autorização de Uso difere do cadastrado',
        223 => 'Rejeição: CNPJ do transmissor do lote difere do CNPJ do transmissor da consulta',
        224 => 'Rejeição: A faixa inicial é maior que a faixa final',
        225 => 'Rejeição: Falha no Schema XML do lote de NFe',
        226 => 'Rejeição: Código da UF do Emitente diverge da UF autorizadora',
        227 => 'Rejeição: Erro na Chave de Acesso - Campo Id – falta a literal NFe',
        228 => 'Rejeição: Data de Emissão muito atrasada',
        229 => 'Rejeição: IE do emitente não informada',
        230 => 'Rejeição: IE do emitente não cadastrada',
        231 => 'Rejeição: IE do emitente não vinculada ao CNPJ',
        232 => 'Rejeição: IE do destinatário não informada',
        233 => 'Rejeição: IE do destinatário não cadastrada',
        234 => 'Rejeição: IE do destinatário não vinculada ao CNPJ',
        235 => 'Rejeição: Inscrição SUFRAMA inválida',
        236 => 'Rejeição: Chave de Acesso com dígito verificador inválido',
        237 => 'Rejeição: CPF do destinatário inválido',
        238 => 'Rejeição: Cabeçalho - Versão do arquivo XML superior a Versão vigente',
        251 => 'Rejeição: UF/Município destinatário não pertence a SUFRAMA',
        252 => 'Rejeição: Ambiente informado diverge do Ambiente de recebimento',
        253 => 'Rejeição: Digito Verificador da chave de acesso composta inválida',
        254 => 'Rejeição: NF-e complementar não possui NF referenciada',
        255 => 'Rejeição: NF-e complementar possui mais de uma NF referenciada',
        256 => 'Rejeição: Uma NF-e da faixa já está inutilizada na Base de dados da SEFAZ',
        589 => 'Rejeição: Número do NSU informado superior ao maior NSU da base de dados da SEFAZ',
        590 => 'Rejeição: Informado CST para emissor do Simples Nacional (CRT=1)',
        591 => 'Rejeição: Informado CSOSN para emissor que não é do Simples Nacional (CRT diferente de 1)',
        662 => 'Rejeição: Numeração do EPEC está inutilizada na Base de Dados da SEFAZ',
        663 => 'Rejeição: Alíquota do ICMS com valor superior a 4 por cento na operação de saída interestadual com produtos importados',
        678 => 'Rejeição: NF referenciada com UF diferente da NF-e complementar',
        817 => 'Rejeição: Unidade Tributável incompatível com o NCM informado na operação com Comércio Exterior',
        999 => 'Rejeição: Erro não catalogado (informar a mensagem de erro capturado no tratamento da exceção)',
    ];


    /** Retorna a descrição de um cStat */
    public static function getDescription(int $cStat): string
    {
        return self::$descriptions[$cStat] ?? 'Status desconhecido';
    }

    /** cStat válidos para envio de lote */
    public static function isValidLote(int $cStat): bool
    {
        return in_array($cStat, [103, 104, 105], true);
    }

    /** cStat válidos para autorização de NF-e */
    public static function isValidNFe(int $cStat): bool
    {
        return in_array($cStat, [100, 101, 102], true);
    }
}

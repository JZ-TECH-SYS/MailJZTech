<?php

namespace src\handlers\NFEs\UtilsNF;

use src\handlers\PedidoVenda;
use Exception;

class Validador
{
    public static function validarPedido($idempresa, $idpedidovenda, $pedido)
    {
        $erros = [];
        // pega o tipo de nota (55 ou 65)
        $tipoNota = (int)($pedido['tipo_nota'] ?? 65);

        // garante sempre um texto em obs
        $pedido['obs'] = !empty($pedido['obs']) ? $pedido['obs'] : 'Sem observações';

        // 1) regras gerais (sempre executadas)
        $validacoes = [
            'total_pedido' => [
                'condicao' => empty($pedido['total_pedido']),
                'mensagem' => "O total do pedido não foi informado. Por favor, verifique o valor total da venda."
            ],
            'emitente_cnpj' => [
                'condicao' => empty($pedido['emitente']['cnpj']),
                'mensagem' => "O CNPJ do emitente é obrigatório. Por favor, verifique as informações da empresa."
            ],
            'emitente_nome' => [
                'condicao' => empty($pedido['emitente']['nome']),
                'mensagem' => "O nome do emitente é obrigatório. Verifique as informações da empresa."
            ],
            'emitente_uf_municipio' => [
                'condicao' => empty($pedido['emitente']['coduf']) || empty($pedido['emitente']['codmunicipio']),
                'mensagem' => "O código UF e o código do município do emitente são obrigatórios. Verifique as informações da empresa."
            ],
            'itens_pedido' => [
                'condicao' => empty($pedido['itens']) || !is_array($pedido['itens']) || count($pedido['itens']) === 0,
                'mensagem' => "O pedido deve conter pelo menos um item. Adicione os produtos para gerar a nota fiscal."
            ],
            'observacoes_pedido' => [
                'condicao' => empty($pedido['obs']),
                'mensagem' => "Observações do pedido estão ausentes. Adicione informações adicionais ou deixe como 'Sem observações'."
            ],
        ];

        // 2) regras de destinatário **só** para NF-e (55)
        if ($tipoNota === 55) {
            $validacoes['destinatario_nome'] = [
                'condicao' => empty($pedido['destinatario']['nome']),
                'mensagem' => "O nome do destinatário é obrigatório. Por favor, preencha o nome do cliente."
            ];
            $validacoes['destinatario_documento'] = [
                'condicao' => empty($pedido['destinatario']['cpf']) && empty($pedido['destinatario']['cnpj']),
                'mensagem' => "O CPF ou CNPJ do destinatário é obrigatório. Verifique os dados do cliente."
            ];
            $validacoes['destinatario_endereco'] = [
                'condicao' => empty($pedido['destinatario']['endereco']),
                'mensagem' => "O endereço do destinatário é obrigatório. Por favor, preencha o endereço."
            ];
            $validacoes['destinatario_codmunicipio'] = [
                'condicao' => empty($pedido['destinatario']['codmunicipio']),
                'mensagem' => "O código do município do destinatário é obrigatório. Verifique as informações de endereço do cliente."
            ];
            $validacoes['destinatario_uf'] = [
                'condicao' => empty($pedido['destinatario']['uf']),
                'mensagem' => "A UF (Estado) do destinatário é obrigatória. Verifique as informações de endereço do cliente."
            ];

            // IE obrigatório para destinatário em NF-e
            if (!empty($pedido['destinatario']['cnpj'])) {
                $validacoes['destinatario_ie'] = [
                    'condicao' => empty($pedido['destinatario']['ie']) && empty($pedido['destinatario']['tipo_ie']),
                    'mensagem' => "Para CNPJ, o tipo de IE deve ser informado ou marcado como ISENTO."
                ];
            } elseif (!empty($pedido['destinatario']['cpf'])) {
                $validacoes['destinatario_ie'] = [
                    'condicao' => !isset($pedido['destinatario']['tipo_ie']) || $pedido['destinatario']['tipo_ie'] !== '9',
                    'mensagem' => "Para CPF, o tipo de IE deve ser '9' (Não contribuinte)."
                ];
            }
        }

        // 3) validação de itens (ID, qtd e preço) — executa sempre
        if (!empty($pedido['itens']) && is_array($pedido['itens'])) {
            foreach ($pedido['itens'] as $index => $item) {
                $validacoes["item_{$index}_idproduto"] = [
                    'condicao' => empty($item['idproduto']),
                    'mensagem' => "O ID do produto no item #" . ($index + 1) . " é obrigatório."
                ];
                $validacoes["item_{$index}_quantidade"] = [
                    'condicao' => empty($item['quantidade']) || floatval($item['quantidade']) <= 0,
                    'mensagem' => "A quantidade do produto no item #" . ($index + 1) . " é obrigatória e deve ser maior que zero."
                ];
                $validacoes["item_{$index}_preco"] = [
                    'condicao' => empty($item['preco']) || floatval($item['preco']) <= 0,
                    'mensagem' => "O preço do produto no item #" . ($index + 1) . " é obrigatório e deve ser maior que zero."
                ];
            }
        }

        // 4) executa todas as validações e coleta erros
        foreach ($validacoes as $campo => $v) {
            if ($v['condicao']) {
                $erros[] = $v['mensagem'];
            }
        }

        // 5) lança exceção se encontrou erros
        if (!empty($erros)) {
            $mensagemErro = "Erros encontrados no pedido:\n\n" . implode("\n", $erros);
            PedidoVenda::logProcessamentoNFE($idempresa, $idpedidovenda, $mensagemErro);
            throw new Exception($mensagemErro);
        }
    }
}

<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco III: lista de itens da NFe, agora com espaçamento mínimo.
 */
trait TraitBlocoIII
{
    protected function blocoIII($y)
    {
        if ($this->flagResume) {
            return $y;
        }

        // MATRIZ que define as frações da largura imprimível (wPrint) para cada coluna:
        $matrix = [
            0.12,           // 12% para “Código”
            $this->descPercent, // ~58% para “Descrição”
            0.08,           // 8% para “Qtde”
            0.09,           // 9% para “UN”
            0.156,          // 15.6% para “Vl Unit”
            0.156           // 15.6% para “Vl Total”
        ];

        // 1) Define o tamanho de fonte: 9pt em bobina ≥ 70mm, 6pt se < 70mm
        $fsize = ($this->paperwidth < 70) ? 6 : 9;

        // Fonte em negrito para cabeçalhos de coluna
        $aFontHeader = [
            'font'  => $this->fontePadrao,
            'size'  => $fsize,
            'style' => 'B'
        ];

        // 2) Desenha os títulos das colunas (“Código”, “Descrição”, “Qtde”, “UN”, “Vl Unit”, “Vl Total”)
        $x0 = $this->margem;
        $this->pdf->textBox(
            $x0,
            $y,
            $this->wPrint * $matrix[0],
            3,
            'Código',
            $aFontHeader,
            'T',
            'L',
            false,
            '',
            true
        );

        $x1 = $x0 + ($this->wPrint * $matrix[0]);
        $this->pdf->textBox(
            $x1,
            $y,
            $this->wPrint * $matrix[1],
            3,
            'Descrição',
            $aFontHeader,
            'T',
            'L',
            false,
            '',
            true
        );

        $x2 = $x1 + ($this->wPrint * $matrix[1]);
        $this->pdf->textBox(
            $x2,
            $y,
            $this->wPrint * $matrix[2],
            3,
            'Qtde',
            $aFontHeader,
            'T',
            'C',
            false,
            '',
            true
        );

        $x3 = $x2 + ($this->wPrint * $matrix[2]);
        $this->pdf->textBox(
            $x3,
            $y,
            $this->wPrint * $matrix[3],
            3,
            'UN',
            $aFontHeader,
            'T',
            'C',
            false,
            '',
            true
        );

        $x4 = $x3 + ($this->wPrint * $matrix[3]);
        $this->pdf->textBox(
            $x4,
            $y,
            $this->wPrint * $matrix[4],
            3,
            'Vl Unit',
            $aFontHeader,
            'T',
            'C',
            false,
            '',
            true
        );

        $x5 = $x4 + ($this->wPrint * $matrix[4]);
        $y1 = $this->pdf->textBox(
            $x5,
            $y,
            $this->wPrint * $matrix[5],
            3,
            'Vl Total',
            $aFontHeader,
            'T',
            'R',
            false,
            '',
            true
        );

        // Avança Y para começar a desenhar os itens
        $y2 = $y + $y1;

        // 3) Desenha os itens, um a um, SEM espaçamento extra
        if ($this->det->length > 0) {
            foreach ($this->itens as $item) {
                // Fonte normal (sem negrito) para os valores
                $aFontItem = [
                    'font'  => $this->fontePadrao,
                    'size'  => $fsize,
                    'style' => 'b'
                ];

                // 3.1 Código
                $this->pdf->textBox(
                    $x0,
                    $y2,
                    $this->wPrint * $matrix[0],
                    $item->height,
                    $item->codigo,
                    $aFontItem,
                    'T',
                    'L',
                    false,
                    '',
                    true
                );

                // 3.2 Descrição (texto completo, sem substr)
                $this->pdf->textBox(
                    $x1,
                    $y2,
                    $this->wPrint * $matrix[1],
                    $item->height,
                    $item->desc,
                    $aFontItem,
                    'T',
                    'L',
                    false,
                    '',
                    false
                );

                // 3.3 Qtde
                $this->pdf->textBox(
                    $x2,
                    $y2,
                    $this->wPrint * $matrix[2],
                    $item->height,
                    $item->qtd,
                    $aFontItem,
                    'T',
                    'R',
                    false,
                    '',
                    true
                );

                // 3.4 UN
                $this->pdf->textBox(
                    $x3,
                    $y2,
                    $this->wPrint * $matrix[3],
                    $item->height,
                    $item->un,
                    $aFontItem,
                    'T',
                    'C',
                    false,
                    '',
                    true
                );

                // 3.5 Vl Unit
                $this->pdf->textBox(
                    $x4,
                    $y2,
                    $this->wPrint * $matrix[4],
                    $item->height,
                    $item->vunit,
                    $aFontItem,
                    'T',
                    'R',
                    false,
                    '',
                    true
                );

                // 3.6 Vl Total
                $this->pdf->textBox(
                    $x5,
                    $y2,
                    $this->wPrint * $matrix[5],
                    $item->height,
                    $item->valor,
                    $aFontItem,
                    'T',
                    'R',
                    false,
                    '',
                    true
                );

                // NÃO avançamos y2 com +0.2 nem nada: o próprio $item->height já “encaixa” cada linha
                $y2 += $item->height;
            }
        }

        // 4) Linha tracejada geral abaixo de todo o bloco de itens
        $this->pdf->dashedHLine(
            $this->margem,
            ($this->bloco3H + $y) + 5,
            $this->wPrint,
            0.8,
            30
        );

        // 5) Retorna “altura do Bloco III + 5mm” para folga antes do próximo bloco
        return ($this->bloco3H + 7) + $y;
    }

    /**
     * calculateHeightItens: calcula a altura (em mm) de cada item,
     * desta vez reduzindo a “altura por linha” para 80% do normal.
     */
    protected function calculateHeightItens($descriptionWidth)
    {
        if ($this->flagResume) {
            return 0;
        }

        // 1) Define o tamanho da fonte: 9pt para bobina ≥ 70mm, senão 6pt
        $fsize = ($this->paperwidth < 70) ? 6 : 9;

        // 2) Altura “bruta” de uma linha de texto em mm:
        //    imagefontheight($fsize) retorna pixels; convertemos a mm com (px/72)*25.4
        $lineHeightPx = imagefontheight($fsize);
        $hfontRaw = ($lineHeightPx / 72) * 25.4;

        // 3) Vamos usar apenas 80% dessa altura, para compactar:
        $hfont = $hfontRaw * 0.7;

        // 4) Instância temporária de Pdf para calcular wordWrap
        $tempPDF = new \NFePHP\DA\Legacy\Pdf();
        $tempPDF->setFont($this->fontePadrao, '', $fsize);

        $htot = 0;
        $this->itens = [];

        foreach ($this->det as $nodeDet) {
            $prod = $nodeDet->getElementsByTagName("prod")->item(0);

            $cProd  = $this->getTagValue($prod, "cProd");
            $xProd  = $this->getTagValue($prod, "xProd"); // texto completo
            $qCom   = (float)$this->getTagValue($prod, "qCom");
            $uCom   = $this->getTagValue($prod, "uCom");
            $vUnCom = number_format((float)$this->getTagValue($prod, "vUnCom"), 2, ",", ".");
            $vProd  = number_format((float)$this->getTagValue($prod, "vProd"), 2, ",", ".");

            // 5) Descobre quantas linhas (nLines) a descrição ocupará
            $nLines = $tempPDF->wordWrap($xProd, $descriptionWidth);

            // 6) Altura do item = nLines * hfont (já com 80%)
            $hItem = ($hfont * $nLines);

            // 7) Guarda no array de itens
            $this->itens[] = (object)[
                'codigo' => $cProd,
                'desc'   => $xProd,
                'qtd'    => $qCom,
                'un'     => $uCom,
                'vunit'  => $vUnCom,
                'valor'  => $vProd,
                'height' => $hItem
            ];

            $htot += $hItem;
        }

        // 8) Retorna soma total das alturas + 0mm de folga extra (já que removemos quase tudo)
        return $htot;
    }
}

<?php

namespace src\handlers\impressao;

/**
 * Classe responsável por carregar e renderizar templates
 * Versão melhorada que remove linhas vazias automaticamente
 */
class TemplateEngine
{
    private static $templatePath = __DIR__ . '/templates/';

    /**
     * Carrega um template e substitui as variáveis
     * Remove automaticamente linhas que ficam vazias
     * Suporta condicionais: {{#if variavel}}conteudo{{/if}}
     */
    public static function render(string $templateName, array $data): string
    {
        $templateFile = self::$templatePath . $templateName . '.template.php';

        if (!file_exists($templateFile)) {
            throw new \Exception("Template {$templateName} não encontrado");
        }

        $template = file_get_contents($templateFile);

        // Processa condicionais primeiro {{#if variavel}}...{{/if}}
        $template = self::processConditionals($template, $data);

        // Substitui as variáveis no template
        // Substitui as variáveis no template (removendo linhas que são só o placeholder)
        foreach ($data as $key => $value) {
            if (trim((string)$value) === '') {
                // remove a linha que contém APENAS o placeholder (com espaços/tabs)
                $pattern = '/^[ \t]*\{\{' . preg_quote($key, '/') . '\}\}[ \t]*\r?\n?/m';
                $template = preg_replace($pattern, '', $template);
            } else {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }
        }


        // Remove variáveis não utilizadas (ficam vazias)
        $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);

        // Remove linhas que ficaram só com espaços em branco
        return self::cleanEmptyLines($template);
    }

    /**
     * Processa condicionais no template
     */
    // TemplateEngine::processConditionals (versão que trata bloco e inline)
    private static function processConditionals(string $tpl, array $data): string
    {
        // 1) Blocos em linhas próprias: remove \n extras ao redor
        $tpl = preg_replace_callback(
            '/^[ \t]*\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}[ \t]*\r?\n?/ms',
            function ($m) use ($data) {
                $v = $m[1];
                $content = $m[2];
                return !empty(trim($data[$v] ?? ''))
                    ? rtrim($content, "\r\n") . "\n"    // mantém 1 quebra
                    : '';                                // remove linha inteira
            },
            $tpl
        );

        // 2) Condicionais inline
        $tpl = preg_replace_callback(
            '/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s',
            function ($m) use ($data) {
                $v = $m[1];
                $content = $m[2];
                return !empty(trim($data[$v] ?? '')) ? $content : '';
            },
            $tpl
        );

        return $tpl;
    }


    /**
     * Remove linhas vazias desnecessárias
     */
    private static function cleanEmptyLines(string $template): string
    {
        $lines = explode("\n", $template);
        $cleanLines = [];
        $previousEmpty = false;

        foreach ($lines as $line) {
            $isEmpty = trim($line) === '';

            // Se não está vazia, sempre adiciona
            if (!$isEmpty) {
                $cleanLines[] = $line;
                $previousEmpty = false;
            }
            // Se está vazia mas a anterior não estava, adiciona uma separação
            elseif (!$previousEmpty) {
                $cleanLines[] = $line;
                $previousEmpty = true;
            }
            // Se está vazia e a anterior também estava, pula (evita múltiplas linhas vazias)
        }

        return implode("\n", $cleanLines);
    }

    /**
     * Lista todos os templates disponíveis
     */
    public static function getAvailableTemplates(): array
    {
        $templates = [];
        $files = glob(self::$templatePath . '*.template.php');

        foreach ($files as $file) {
            $templates[] = basename($file, '.template.php');
        }

        return $templates;
    }
}

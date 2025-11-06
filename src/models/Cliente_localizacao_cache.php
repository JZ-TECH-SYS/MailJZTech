<?php

namespace src\models;

use \core\Model;

/**
 * Classe modelo para a tabela 'cliente_localizacao_cache'
 * Gerencia cache de localizações de clientes para delivery
 */
class Cliente_localizacao_cache extends Model
{
    /**
     * Buscar última localização válida do cliente
     * Query SIMPLES - Usa Hydrahon
     */
    public static function getByCliente($endereco_completo)
    {
        $result = Cliente_localizacao_cache::select()
            ->where('endereco_completo', $endereco_completo)
            ->orderBy('updated_at', 'DESC')
            ->one();

        return $result;
    }

    /**
     * Merge de valores priorizando não vazios
     * @param mixed $novo Novo valor
     * @param mixed $existente Valor existente
     * @param mixed $default Valor padrão
     * @return mixed
     */
    private static function mergeValue($novo, $existente = null, $default = null)
    {
        // Verifica se novo valor é válido (não null, não string vazia, não só espaços)
        if ($novo !== null && $novo !== '' && (!is_string($novo) || trim($novo) !== '')) {
            return $novo;
        }

        // Verifica se valor existente é válido
        if ($existente !== null && $existente !== '' && (!is_string($existente) || trim($existente) !== '')) {
            return $existente;
        }

        // Retorna default
        return $default;
    }

    /**
     * Salvar ou atualizar localização do cliente
     * UPDATE/INSERT SIMPLES - Usa Hydrahon
     */
    public static function saveOrUpdate($dados)
    {
        $existing = Cliente_localizacao_cache::getByCliente($dados['endereco_completo']);

        if ($existing) {
            $updateData = [
                'endereco_completo' => self::mergeValue($dados['endereco_completo'] ?? null, $existing['endereco_completo'], null),
                'cep'               => self::mergeValue($dados['cep'] ?? null, $existing['cep'], null),
                'numero'            => self::mergeValue($dados['numero'] ?? null, $existing['numero'], ''),
                'complemento'       => self::mergeValue($dados['complemento'] ?? null, $existing['complemento'], null),
                'bairro'            => self::mergeValue($dados['bairro'] ?? null, $existing['bairro'], ''),
                'cidade'            => self::mergeValue($dados['cidade'] ?? null, $existing['cidade'], ''),
                'uf'                => self::mergeValue($dados['uf'] ?? null, $existing['uf'], ''),
                'latitude'          => self::mergeValue($dados['latitude'] ?? null, $existing['latitude'], null),
                'longitude'         => self::mergeValue($dados['longitude'] ?? null, $existing['longitude'], null),
                'place_id'          => self::mergeValue($dados['place_id'] ?? null, $existing['place_id'], null),
                'precision_source'  => self::mergeValue($dados['precision_source'] ?? null, $existing['precision_source'], 'manual'),
                'validated_at'      => self::mergeValue($dados['validated_at'] ?? null, $existing['validated_at'], null)
            ];
            Cliente_localizacao_cache::update($updateData)
                ->where('idcache', $existing['idcache'])
                ->execute();

            return (int) $existing['idcache'];
        }

        // INSERT simples - Usa Hydrahon
        return Cliente_localizacao_cache::insert([
            'idempresa' => $dados['idempresa'] ?? null,
            'idcliente' => $dados['idcliente'] ?? null,
            'endereco_completo' => $dados['endereco_completo'] ?? null,
            'cep' => $dados['cep'] ?? null,
            'numero' => $dados['numero'] ?? '',
            'complemento' => $dados['complemento'] ?? null,
            'bairro' => $dados['bairro'] ?? '',
            'cidade' => $dados['cidade'] ?? '',
            'uf' => $dados['uf'] ?? '',
            'latitude' => $dados['latitude'] ?? null,
            'longitude' => $dados['longitude'] ?? null,
            'place_id' => $dados['place_id'] ?? null,
            'precision_source' => $dados['precision_source'] ?? 'manual',
            'validated_at' => $dados['validated_at'] ?? null
        ])->execute();
    }
}

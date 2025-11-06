<?php

/**
 * Desc: Classe helper para gerenciar Pessoas no sistema
 * Autor: Joaosn
 * Data de Início: 23/05/2023
 * Data de Alteração: 08/06/2023 19:24
 */

namespace src\handlers;

use src\models\Bairros as BairrosModel;
use Exception;
use src\models\Endereco as EnderecoModel;
use core\Database as db;

class Bairro
{
    const RETORNO = [
        'bairros.idbairro',
        'bairros.nome',
        'bairros.taxa',
        'bairros.status',
        'bairros.idempresa',
        'c.id    as idcidade',
        'c.nome  as cidade',
        'es.nome as estado',
        'es.uf'
    ];

    /**
     * @var \PDOStatement
     */
    public static function condicao ()
    {
        return BairrosModel::select(self::RETORNO)
        ->leftjoin('cidade as c','c.id','=','bairros.idcidade')
        ->leftjoin('estado as es','es.id','=','c.uf');
    }

    /**
     * Busca todas as pessoas associadas a uma empresa específica.
     *
     * @param int $idempresa O ID da empresa.
     * @param int|null $idcidade ID da cidade para filtrar (opcional)
     * @param string|null $cep CEP para busca Auto-CEP (opcional)
     * @return array Lista de pessoas associadas à empresa.
     */
    public static function getBairros($idempresa, $idcidade = null, $cep = null)
    {
        // Se tem CEP e Auto-CEP está habilitado, usar busca inteligente
        if ($cep && AutoCep::isAutoCepEnabled($idempresa)) {
            return self::getBairrosComAutoCep($idempresa, $idcidade, $cep);
        }

        // Comportamento padrão - todos os bairros da empresa
        $query = self::condicao()->where('bairros.idempresa', $idempresa);
        
        // Se especificou cidade, filtrar por ela
        if ($idcidade) {
            $query = $query->where('bairros.idcidade', $idcidade);
        }
        
        $bairros = $query->execute();

        // Se nenhuma correspondência foi encontrada para a cidade informada, retorna todos os bairros da empresa
        if (empty($bairros) && !empty($idcidade)) {
            $bairros = self::condicao()
                ->where('bairros.idempresa', $idempresa)
                ->execute();
        }

        return $bairros;
    }

    /**
     * Busca bairros usando Auto-CEP via ViaCEP
     */
    private static function getBairrosComAutoCep($idempresa, $idcidade, $cep)
    {
        try {
            // Limpa o CEP (remove tudo que não for número)
            $cepLimpo = preg_replace('/[^0-9]/', '', $cep);
            
            if (strlen($cepLimpo) !== 8) {
                error_log("Auto-CEP: CEP inválido - {$cep}");
                return self::getBairros($idempresa, $idcidade); // Fallback
            }

            // 🚀 PRIMEIRO: Tenta buscar no CACHE
            $cacheResult = \src\handlers\AutoCep::buscarCachePorCep($idempresa, $cepLimpo);
            
            $dadosEndereco = null;
            
            if ($cacheResult['success']) {
                // Se achou no cache, usa os dados do cache
                $dadosEndereco = $cacheResult['dados'];
                error_log("Auto-CEP: Dados encontrados no CACHE para CEP: {$cepLimpo}");
            } else {
                // Se não tem no cache, busca no ViaCEP
                $resultadoViaCep = \src\handlers\AutoCep::consultarViaCep($cepLimpo);
               
                if ($resultadoViaCep['success']) {
                    $dadosEndereco = $resultadoViaCep['dados'];
                    
                    // Salva no cache para próximas consultas
                    \src\handlers\AutoCep::salvarCacheCep($idempresa, $dadosEndereco, $cepLimpo);
                    error_log("Auto-CEP: Dados encontrados no ViaCEP para CEP: {$cepLimpo}");
                } else {
                    error_log("Auto-CEP: Não conseguiu resolver CEP no ViaCEP: " . ($resultadoViaCep['error'] ?? 'erro desconhecido'));
                }
            }
            
            // Se conseguiu dados do endereço e tem bairro
            if ($dadosEndereco && isset($dadosEndereco['bairro']) && trim($dadosEndereco['bairro']) !== '') {
                $bairroViaCep = trim($dadosEndereco['bairro']);
                $bairroViaCepLower = strtolower($bairroViaCep);
                
                // Buscar bairros que correspondem ao resultado
                $bairrosTodos = self::condicao()
                    ->where('bairros.idempresa', $idempresa)
                    ->where('bairros.idcidade', $idcidade)
                    ->execute();
                
                $bairrosFiltrados = [];
                $melhorMatch = null;
                $melhorScore = 0;
                
                foreach ($bairrosTodos as $bairro) {
                    $bairroLocal = strtolower($bairro['nome']);
                    $score = 0;
                    
                    // 1. Match exato (score máximo)
                    if ($bairroLocal === $bairroViaCepLower) {
                        $score = 100;
                    }
                    // 2. Match normalizado (remove acentos, espaços, etc)
                    else if (self::normalizeString($bairroLocal) === self::normalizeString($bairroViaCepLower)) {
                        $score = 90;
                    }
                    // 3. Bairro local contém o do ViaCEP
                    else if (strpos($bairroLocal, $bairroViaCepLower) !== false) {
                        $score = 80;
                    }
                    // 4. ViaCEP contém o bairro local
                    else if (strpos($bairroViaCepLower, $bairroLocal) !== false) {
                        $score = 70;
                    }
                    // 5. Match por palavras-chave (ex: "São Judas" em "Núcleo Habitacional São Judas Tadeu")
                    else {
                        $palavrasLocal = explode(' ', $bairroLocal);
                        $palavrasViaCep = explode(' ', $bairroViaCepLower);
                        $palavrasComuns = 0;
                        
                        foreach ($palavrasLocal as $palavra) {
                            if (strlen($palavra) > 2) { // Ignora palavras muito pequenas
                                foreach ($palavrasViaCep as $palavraVia) {
                                    if (strpos($palavraVia, $palavra) !== false || strpos($palavra, $palavraVia) !== false) {
                                        $palavrasComuns++;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        if ($palavrasComuns > 0) {
                            $score = min(60, $palavrasComuns * 20); // Máximo 60 pontos
                        }
                    }
                    
                    // Se tem um score bom, adiciona aos resultados
                    if ($score >= 70) {
                        // Adiciona os dados do ViaCEP ao resultado
                        $bairro['endereco_auto'] = [
                            'logradouro' => $dadosEndereco['logradouro'] ?? '',
                            'bairro' => $dadosEndereco['bairro'] ?? '',
                            'cidade' => $dadosEndereco['localidade'] ?? '',
                            'uf' => $dadosEndereco['uf'] ?? '',
                            'cep' => $dadosEndereco['cep'] ?? $cepLimpo
                        ];
                        $bairro['match_score'] = $score;
                        
                        $bairrosFiltrados[] = $bairro;
                        
                        // Guarda o melhor match
                        if ($score > $melhorScore) {
                            $melhorScore = $score;
                            $melhorMatch = $bairro;
                        }
                    }
                }
                
                if (!empty($bairrosFiltrados)) {
                    // Ordena por score (melhor primeiro)
                    usort($bairrosFiltrados, function($a, $b) {
                        return ($b['match_score'] ?? 0) - ($a['match_score'] ?? 0);
                    });
                    
                    error_log("Auto-CEP: Encontrados " . count($bairrosFiltrados) . " bairros para CEP {$cepLimpo} - Bairro: '{$bairroViaCep}' - Melhor match: '{$melhorMatch['nome']}' (Score: {$melhorScore})");
                    return $bairrosFiltrados;
                }
                
                // Se não encontrou match exato, retorna dados do ViaCEP para criação manual
                error_log("Auto-CEP: Nenhum bairro local encontrado para '{$bairroViaCep}' - retornando dados ViaCEP");
                return [[
                    'idbairro' => 0,
                    'nome' => $bairroViaCep,
                    'taxa' => 0,
                    'status' => 1,
                    'idempresa' => $idempresa,
                    'idcidade' => $idcidade,
                    'cidade' => $dadosEndereco['localidade'] ?? '',
                    'estado' => '',
                    'uf' => $dadosEndereco['uf'] ?? '',
                    'endereco_auto' => [
                        'logradouro' => $dadosEndereco['logradouro'] ?? '',
                        'bairro' => $dadosEndereco['bairro'] ?? '',
                        'cidade' => $dadosEndereco['localidade'] ?? '',
                        'uf' => $dadosEndereco['uf'] ?? '',
                        'cep' => $dadosEndereco['cep'] ?? $cepLimpo
                    ]
                ]];
            }
            
        } catch (Exception $e) {
            error_log("Erro Auto-CEP: " . $e->getMessage());
        }
        
        // Fallback: retornar todos os bairros da cidade
        error_log("Auto-CEP: Fallback - retornando todos os bairros disponíveis");
        return self::getBairros($idempresa, null);
    }

    /**
     * Normaliza string removendo acentos
     */
    private static function normalizeString($str)
    {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $str));
    }

    /**
     * Calcula score de matching entre dois nomes de bairro
     * 
     * @param string $bairroLocal Nome do bairro no sistema
     * @param string $bairroViaCep Nome do bairro do ViaCEP
     * @return int Score de 0 a 100
     */
    public static function calculateMatchScore($bairroLocal, $bairroViaCep)
    {
        $localLower = strtolower(trim($bairroLocal));
        $viaCepLower = strtolower(trim($bairroViaCep));
        
        // 1. Match exato (100 pontos)
        if ($localLower === $viaCepLower) {
            return 100;
        }
        
        // 2. Match normalizado (90 pontos)
        if (self::normalizeString($localLower) === self::normalizeString($viaCepLower)) {
            return 90;
        }
        
        // 3. Um contém o outro (80-70 pontos)
        if (strpos($localLower, $viaCepLower) !== false) {
            return 80;
        }
        if (strpos($viaCepLower, $localLower) !== false) {
            return 70;
        }
        
        // 4. Match por palavras-chave (60 pontos)
        $localWords = explode(' ', $localLower);
        $viaCepWords = explode(' ', $viaCepLower);
        
        $matchCount = 0;
        $totalWords = count($viaCepWords);
        
        foreach ($viaCepWords as $viaCepWord) {
            if (strlen($viaCepWord) >= 3) { // Só considera palavras com 3+ caracteres
                foreach ($localWords as $localWord) {
                    if (strpos($localWord, $viaCepWord) !== false || strpos($viaCepWord, $localWord) !== false) {
                        $matchCount++;
                        break;
                    }
                }
            }
        }
        
        if ($matchCount > 0 && $totalWords > 0) {
            $percentage = ($matchCount / $totalWords) * 100;
            if ($percentage >= 50) { // Se pelo menos 50% das palavras fazem match
                return 60;
            }
        }
        
        return 0; // Sem match
    }

    /**
     * Busca uma pessoa específica associada a uma empresa usando o ID da pessoa.
     *
     * @param int $idempresa O ID da empresa.
     * @param int $idcliente O ID da pessoa (cliente).
     * @return array|bool Dados da pessoa encontrada ou false se não encontrada.
     */
    public static function getBairroById($idempresa, $idbairro)
    {
        $pessoa = self::condicao(self::RETORNO)
                    ->where('bairros.idempresa', $idempresa)
                    ->where('bairros.idbairro', $idbairro)
        ->one();
        return $pessoa;
    }

    public static function addBairro($data)
    {
        // Verifica se o CPF já está cadastrado para a empresa específica
        $isBairro = BairrosModel::select()
                   ->where('nome',$data['nome'])
                   ->where('idempresa',$data['idempresa'])
                ->one();
        if (!empty($isBairro)) {
            throw new Exception('Bairro já cadastrado');
        }

        try{
            db::getInstance()->beginTransaction();
            $dadosbairro = [
                'idempresa'   => $data['idempresa'],
                'nome'        => $data['nome']       ?? null,
                'taxa'        => $data['taxa']       ?? null,
                'status'      => $data['status']     ??    2, 
                'idcidade'    => $data['idcidade']     ?? null 
            ];
            $id =  BairrosModel::insert($dadosbairro)->execute();
            db::getInstance()->commit();
        }catch(Exception $e){
            db::getInstance()->rollBack();
            throw new Exception('Erro ao cadastrar pessoa Contate o administrador do sistema!');
        }
      
        // Retorna a pessoa recém-criada usando o ID da empresa e o ID da pessoa
        return self::getBairroById($data['idempresa'], $id);
    }

    /**
     * Atualiza os detalhes de uma pessoa no sistema e retorna a pessoa atualizada.
     *
     * @param array $data Dados atualizados da pessoa.
     * @return array Dados da pessoa atualizada.
     */
    public static function editBairro($data)
    {
        // Obtém a pessoa que será editada usando o ID da empresa e o ID da pessoa
        $isBairro = BairrosModel::select()->where('idbairro',$data['idbairro'])->one();
        if (empty($isBairro)) {
            throw new Exception('Bairro não encontrado');
        }

        if ( ($isBairro['nome'] != $data['nome']) ) {
            // Verifica se o BAIRRO já está cadastrado para a empresa específica
            $jaexist = BairrosModel::select()
                    ->where('idempresa', $data['idempresa'])
                    ->whereNotIn('idbairro',[$isBairro['idcliente']])   
                    ->where('nome', $data['nome'])
                    
            ->one();
            if (!empty($jaexist)) {
                throw new Exception('Bairro já cadastrado');
            }
        }

        // Atualiza os detalhes da pessoa no banco de dados usando os dados fornecidos
        // Se os dados não forem fornecidos, mantém os valores existentes
        $dadosbairro = [
            'nome'          => $data['nome']     ?? $isBairro['nome'],
            'taxa'          => $data['taxa']     ?? $isBairro['taxa'],
            'status'        => $data['status']   ?? $isBairro['status'],
            'idcidade'      => $data['idcidade'] ?? $isBairro['idcidade']
        ];

        try{
            db::getInstance()->beginTransaction();
            BairrosModel::update($dadosbairro)
                ->where('idempresa', $data['idempresa'])
                ->where('idbairro', $data['idbairro'])
            ->execute();
            db::getInstance()->commit();
        }catch(Exception $e){
            db::getInstance()->rollBack();
            throw new Exception('Erro ao atualizar Bairro Contate o administrador do sistema!');
        }
    
        // Retorna a pessoa atualizada usando o ID da empresa e o ID da pessoa
        return self::getBairroById($data['idempresa'], $data['idbairro']);
    }

    /**
     * Exclui uma pessoa do sistema e retorna uma mensagem de sucesso.
     *
     * @param array $data Dados da pessoa a serem excluídos (ID da empresa e ID da pessoa).
     * @return array Mensagem indicando que a pessoa foi excluída com sucesso.
     */
    public static function deleteBairro($data)
    {
        try{
            // Remove a pessoa do banco de dados usando o ID da empresa e o ID da pessoa
            BairrosModel::delete()
                ->where('idempresa', $data['idempresa'])
                ->where('idbairro', $data['idbairro'])
            ->execute();
        }catch(Exception $e){
            throw new Exception('Erro ao excluir Bairro Contate o administrador do sistema!');
        }
        // Retorna uma mensagem indicando que a pessoa foi excluída com sucesso
        return ['message' => 'Bairro excluída com sucesso'];
    }

}

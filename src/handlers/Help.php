<?php
/**
 * Classe responsável por ter varias funções auxiliares
 *
 * @autor: joaosn
 * @dateInicio: 23/05/2023
 */

namespace src\handlers;

use core\Database;
use DateTime;
use src\models\Empresa;
use src\models\Pessoa;
use src\models\Endereco;
use src\models\Empresa_parametro;
use src\models\Empresa_horarios;
use src\models\Pedido_venda;
use src\models\Cupon_regra;
use src\models\Cupon as CuponModel;
use src\models\Cupon_extra;
use src\models\Cupon_pedidos;

/**
 * Classe Help com funções auxiliares
 * 
 * @package src\handlers
 */
class Help
{
   /**
    * Obtém o número de celular associado a um pedido.
    * Verifica se o campo 'obs' contém um número de celular válido.
    * Caso contrário, busca o número de celular no cadastro do cliente.
    *
    * @param array $pedido Dados do pedido.
    * @return string Número de celular ou string vazia se não encontrado.
    */
   public static function getCellPedido(array $pedido): string
   {
      // Verifica se 'obs' já é um array e contém o campo 'celular'
      if (!empty($pedido['obs']) && is_array($pedido['obs']) && !empty($pedido['obs']['celular'])) {
         return self::formatarTelefone($pedido['obs']['celular']);
      }

      // Busca o número de celular no cadastro do cliente
      $cliente = Pessoa::select()
         ->where('idcliente', $pedido['idcliente'])
         ->where('idempresa', $pedido['idempresa'])
         ->one();

      if (empty($cliente) || empty($cliente['celular'])) return '';

      return self::formatarTelefone($cliente['celular']) ?? '';
   }
   /**
    * Verifica se há valores vazios em um array
    *
    * @param array $dados Array a ser verificado
    * @return bool Retorna true se todos os valores forem preenchidos, false caso contrário
    */
   public function verificaVariavel(array $dados)
   {
      foreach ($dados as $item) {
         if (empty($item) && !isset($item)) {
            return false;
            exit;
         }
      }
      return true;
   }

   private static function getMesa($idempresa, $mesa)
   {
      return Pedido_venda::select()->where('idempresa', $idempresa)->where('idmesa', $mesa)->one();
   }

   public static function gerarMesa($idempresa)
   {
      $mesa = rand(1, 999);
      $mesaJaExiste = self::getMesa($idempresa, $mesa);
      if (!empty($mesaJaExiste)) {
         self::gerarMesa($idempresa);
      }
      return $mesa;
   }

   public static function validarMesa($idempresa, $mesa)
   {
      $mesaJaExiste = self::getMesa($idempresa, $mesa);
      if (!empty($mesaJaExiste)) {
         throw new \Exception("Mesa já está em uso por outro cliente. Por favor, escolha outra mesa.");
      }
   }

   public static function getImgBase64($img)
   {
      $PATH = './../public/images/';
      $LINK = explode('/', $img);
      $nome = end($LINK);

      // Verificação se o nome do arquivo está vazio
      if (empty($nome)) {
         return false;
      }

      $img = $PATH . $nome;

      if (!file_exists($img)) {
         return false;
      }

      $type = pathinfo($img, PATHINFO_EXTENSION);

      // Fallback para um tipo de arquivo padrão se não houver extensão
      if (!$type) {
         $type = 'jpeg'; // ou 'png', dependendo do que for mais comum
      }

      $data = file_get_contents($img);
      $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
      return $base64;
   }


   /**
    * Formata um valor para o padrão de moeda brasileiro
    *
    * @param float|string $value Valor a ser formatado
    * @return string Valor formatado em R$ X,XX
    */
   public static function currencyBanco($value)
   {
      // Verifique se o valor já está no formato correto (ex: 1234.56)
      if (preg_match('/^\d+(\.\d{2})?$/', $value)) {
         return $value;
      }

      // Remova todos os caracteres não numéricos, exceto vírgulas e pontos
      $value = preg_replace('/[^\d.,]/', '', $value);

      // Substitua vírgulas por pontos para garantir o formato decimal correto
      $value = str_replace(',', '.', $value);

      // Divida a string em partes inteira e decimal usando o ponto como separador
      $parts = explode('.', $value);

      // Verifique se há uma parte decimal e se ela tem mais de dois dígitos
      if (count($parts) > 1 && strlen($parts[1]) > 2) {
         // Mantenha apenas os dois primeiros dígitos da parte decimal
         $parts[1] = substr($parts[1], 0, 2);
      }

      // Reconstrua o valor usando o ponto como separador decimal
      $formattedValue = implode('.', $parts);

      // Retorne o valor formatado
      return $formattedValue;
   }

   /**
    * Formata um número para o padrão de moeda brasileiro
    *
    * @param float|string $numero Número a ser formatado
    * @return string Número formatado em X.XXX,XX ou "Gratis" caso seja igual a "Gratis"
    */
   public function BRL($numero)
   {
      $num = ($numero == 'Gratis') ? $numero : number_format($numero, 2, ',', '.');
      return $num;
   }

   /**
    * Remove um arquivo de imagem
    *
    * @param string $item Nome do arquivo a ser removido
    * @return void
    */
   public function removeItem($item)
   {
      if (!empty($item)) {
         $filename = './../public/images/' . $item;
         if (file_exists($filename)) {
            unlink($filename);
         }
      }
   }


   /**
    * retorna true se array passado e muti array 
    * @param array $array
    * @return boolval
    */
   public static function is_multidimensional(array $array): bool
   {
      foreach ($array as $value) {
         if (is_array($value)) {
            return true;
         }
      }
      return false;
   }

   /**
    * get infos telas de pedidos Online
    */
   public static function getInfoPsedidoOn($nome_empresa)
   {
      $infos = Empresa::getInfosPedidoOn($nome_empresa);
      if (!empty($infos)) {
         $aberto = Help::estaAberto($infos['idempresa']);
         $infos['esta_aberta'] = $aberto;
         
         // Registra lead quando acessa informações da empresa
         try {
            LeadsHandler::handle($infos['idempresa']);
         } catch (\Exception $e) {
            // Silently continue if leads logging fails
         }
      } else {
         $infos = false;
      }

      return $infos;
   }

   /**
    * get infos telas de pedidos Online parametros
    */
   public static function getInfoSiteParams($idempresa)
   {
      $infos = Empresa::getInfosPedidoOn($idempresa);
      if (!empty($infos)) {
         // Normaliza flags booleanas que podem vir como 'true'|'false', 1|0 ou boolean
         $infos['esta_aberta'] = self::toBool($infos['esta_aberta'] ?? false);
         $infos['ativar_cupon'] = self::toBool($infos['ativar_cupon'] ?? false);
         // A origem no banco é 'usar_geolocalizacao' (parametro 17). Exponha como 'usar_geolocalizacao_delivery'.
         $origGeo = $infos['usar_geolocalizacao'] ?? ($infos['usar_geolocalizacao_delivery'] ?? false);
         $infos['usar_geolocalizacao_delivery'] = self::toBool($origGeo);
         
      } else {
         $infos = false;
      }

      $infos = array_merge($infos, Empresa::regraCuponDesconto($idempresa));

      $horarios = Empresa_horarios::select()
         ->where('idempresa', $idempresa)
         ->orderBy('dia_semana', 'ASC')
         ->get();

      $diasSemana = [];
      foreach ($horarios as $h) {
         $diasSemana[] = [
            'dia' => (int) $h['dia_semana'],
            'aberto' => ($h['aberto'] ?? '1') == '1' ? true : false,
            'hora_abertura' => $h['hora_abertura'],
            'hora_fechamento' => $h['hora_fechamento']
         ];
      }

      $infos['campos_adicionais_checkout'] = json_encode($infos['campos_adicionais_checkout'] ?? []);
      $infos['dias_semana'] = $diasSemana;
      return $infos;
   }

   /**
    * Normaliza valores para booleano aceitando strings ('true','1','on','yes'), inteiros (1/0) e boolean.
    */
   public static function toBool($value): bool
   {
      if (is_bool($value)) return $value;
      if (is_int($value)) return $value === 1;
      if (is_numeric($value)) return (int)$value === 1;
      if (is_string($value)) {
         $v = strtolower(trim($value));
         return in_array($v, ['1', 'true', 't', 'on', 'yes', 'y'], true);
      }
      return false;
   }

   /**
    * get infos cliente por numero 
    */
   public static function getInfosCliente($numero, $idempresa)
   {
      $cliente = Pessoa::select()->where('celular', $numero)->where('idempresa', $idempresa)->one();
      $endereco = [];
      $pedidos = [];
      if (!empty($cliente)) {
         $endereco = Endereco::select()->where('idcliente', $cliente['idcliente'])->one();
         //$pedidos = Pedido_venda::select()->where('idcliente', $cliente['idcliente'])->where('idempresa', $idempresa)->where('idsituacao_pedido_venda', 2)->orderBy('idpedidovenda', 'DESC')->get();
      }
      $cupon = Cupon::getCuponByTelefone($numero, $idempresa);
      return compact('cliente', 'endereco', 'pedidos', 'cupon');
   }

   /**
    * abre ou fecha o site
    * @param string $action abrir/fechar
    * @param int $idempresa
    */
   public static function abreFechaSite($action, $idempresa)
   {
      switch ($action) {
         case 'abrir':
            $aberto = 'true';
            break;
         case 'fechar':
            $aberto = 'false';
            break;
         default:
            $aberto = 'false';
            break;
      }

      Empresa_parametro::update([
         'valor' =>  $aberto
      ])
         ->where('idempresa', $idempresa)
         ->where('idparametro', 4)
         ->execute();
   }

   public static function estaAberto($idempresa)
   {
      // --- 1. CONFIGURAÇÃO INICIAL ---
      // Parâmetro mestre (habilita/desabilita site)
      $parametroMestre = Empresa_parametro::select()
         ->where('idempresa', $idempresa)
         ->where('idparametro', 4)
         ->one();

      if (!$parametroMestre || $parametroMestre['valor'] !== 'true') {
         // Se o controle manual está "fechado", nada mais importa.
         return false;
      }

      // Obtém data e hora atuais. Usar objetos DateTime é mais seguro para comparações.
      $agora = new DateTime();
      $diaSemanaAtual = (int)$agora->format('w'); // 0=Dom, 1=Seg, ..., 6=Sáb
      $horaAtualStr = $agora->format('H:i:s');

      // --- 2. LÓGICA DE VERIFICAÇÃO DE HORÁRIO ---

      // **PASSO A: VERIFICAR O RASTRO DO DIA ANTERIOR**
      // Esta é a verificação mais importante para horários que cruzam a meia-noite.
      $diaSemanaOntem = ($diaSemanaAtual === 0) ? 6 : $diaSemanaAtual - 1; // Se hoje é Dom(0), ontem foi Sáb(6)
      $horarioOntem = Empresa_horarios::select()
         ->where('idempresa', $idempresa)
         ->where('dia_semana', $diaSemanaOntem)
         ->one();

      if ($horarioOntem && (int)$horarioOntem['aberto'] === 1) {
         $aberturaOntem = $horarioOntem['hora_abertura'];
         $fechamentoOntem = $horarioOntem['hora_fechamento'];

         // Se o fechamento de ontem é menor que a abertura, significa que vira a noite.
         // Ex: Abre 17:00, Fecha 01:30
         if ($fechamentoOntem < $aberturaOntem) {
            // Se a hora atual for menor que a hora de fechamento de ontem,
            // então ainda estamos no expediente de ontem.
            // Ex: São 01:00 de sexta, e o fechamento de quinta era 01:30. Está aberto.
            if ($horaAtualStr < $fechamentoOntem) {
               return true; // ABERTO (pelo rastro de ontem)
            }
         }
      }

      // **PASSO B: VERIFICAR O HORÁRIO DO DIA ATUAL**
      // Se não estamos no rastro de ontem, verificamos o expediente de hoje.
      $horarioHoje = Empresa_horarios::select()
         ->where('idempresa', $idempresa)
         ->where('dia_semana', $diaSemanaAtual)
         ->one();

      // Se hoje não tem horário definido ou está marcado como fechado, então está fechado.
      if (!$horarioHoje || (int)$horarioHoje['aberto'] !== 1) {
         self::abreFechaSite('fechar', $idempresa);
         return false;
      }

      $aberturaHoje = $horarioHoje['hora_abertura'];
      $fechamentoHoje = $horarioHoje['hora_fechamento'];

      // Horários zerados significam fechado.
      if ($aberturaHoje === '00:00:00' && $fechamentoHoje === '00:00:00') {
         self::abreFechaSite('fechar', $idempresa);
         return false;
      }

      $estaAbertoHoje = false;
      if ($fechamentoHoje > $aberturaHoje) {
         // Caso normal: expediente no mesmo dia (ex: 09:00 às 18:00)
         $estaAbertoHoje = ($horaAtualStr >= $aberturaHoje && $horaAtualStr < $fechamentoHoje);
      } else {
         // Caso de virada: expediente cruza a meia-noite (ex: 17:00 às 03:00)
         // A verificação da madrugada já foi feita no PASSO A.
         // Aqui, só precisamos verificar se já passou do horário de abertura.
         $estaAbertoHoje = ($horaAtualStr >= $aberturaHoje);
      }

      if (!$estaAbertoHoje) {
         self::abreFechaSite('fechar', $idempresa);
      }

      return $estaAbertoHoje;
   }


   public static function editInfosPedidoOn($dados)
   {
      $dados['esta_aberta'] = self::toBool($dados['esta_aberta']);
      $dados['ativar_cupon'] = self::toBool($dados['ativar_cupon']);
      $dados['usar_geolocalizacao'] = self::toBool($dados['usar_geolocalizacao_delivery']);
      try {
         $db = Database::getInstance();
         $db->beginTransaction();
         $getEmpresa = Empresa::select()->where('idempresa', $dados['idempresa'])->one();
         $params = [
            3 => 'horario_atendimento',
            4 => 'esta_aberta',
            5 => 'tempo_espera',
            6 => 'logo',
            7 => 'banner_fundo',
            8 => 'hora_abertura',
            9 => 'hora_fechamento',
            11 => 'ativar_cupon',
            14 => 'campos_adicionais_checkout',
            17 => 'usar_geolocalizacao'
         ];

         Empresa::update([
            'nomefantasia' => $dados['nomefantasia'] ?? $getEmpresa['nomefantasia'],
            'cnpj'         => $dados['cnpj']         ?? $getEmpresa['cnpj'],
            'endereco'     => $dados['endereco']     ?? $getEmpresa['endereco'],
            'numero'       => $dados['numero']       ?? $getEmpresa['numero'],
            'dilema'       => $dados['dilema']       ?? $getEmpresa['dilema'],
            'idcidade'     => $dados['idcidade']     ?? $getEmpresa['idcidade'],
            'chavepix'     => $dados['chavepix']     ?? $getEmpresa['chavepix'],
            'cell'         => $dados['cell']         ?? $getEmpresa['cell']
         ])->where('idempresa', $dados['idempresa'])
            ->execute();

         foreach ($params as $idparametro => $field) {
            $paramValue = Empresa_parametro::select()
               ->where('idempresa', $dados['idempresa'])
               ->where('idparametro', $idparametro)
               ->one();

            Empresa_parametro::update([
               'valor' => $dados[$field] ?? $paramValue['valor']
            ])->where('idempresa', $dados['idempresa'])
               ->where('idparametro', $idparametro)
               ->execute();
         }

         if (!empty($dados['dias_semana']) && is_array($dados['dias_semana'])) {
            foreach ($dados['dias_semana'] as $dia) {
               $diaSemana = (int)($dia['dia'] ?? 0);
               $aberto = !empty($dia['aberto']);
               $horaAbertura = $aberto ? ($dia['hora_abertura'] ?? '00:00:00') : '00:00:00';
               $horaFechamento = $aberto ? ($dia['hora_fechamento'] ?? '00:00:00') : '00:00:00';

               $existe = Empresa_horarios::select()
                  ->where('idempresa', $dados['idempresa'])
                  ->where('dia_semana', $diaSemana)
                  ->one();

               if ($existe) {
                  Empresa_horarios::update([
                     'aberto' => $aberto ? 1 : 0,
                     'hora_abertura' => $horaAbertura,
                     'hora_fechamento' => $horaFechamento
                  ])->where('idempresa', $dados['idempresa'])
                     ->where('dia_semana', $diaSemana)
                     ->execute();
               } else {
                  Empresa_horarios::insert([
                     'idempresa' => $dados['idempresa'],
                     'dia_semana' => $diaSemana,
                     'aberto' => $aberto ? 1 : 0,
                     'hora_abertura' => $horaAbertura,
                     'hora_fechamento' => $horaFechamento
                  ])->execute();
               }
            }
         }

         // --- CUPONS: atualizar/criar regra de cupom quando ativo ---
         $cuponIsActive = (($dados['ativar_cupon'] ?? 'false') === 'true');
         if ($cuponIsActive) {
            // Normaliza e sanitiza campos recebidos (aceita número ou string BRL)
            $valor       = isset($dados['valor_cupon']) ? self::currencyBanco($dados['valor_cupon']) : null;
            $descricao   = $dados['descricao_cupon'] ?? null;
            $qtdPedidos  = isset($dados['quantidade_cupon']) ? (int) $dados['quantidade_cupon'] : null;
            $valorMinimo = isset($dados['valor_minimo_cupon']) ? self::currencyBanco($dados['valor_minimo_cupon']) : null;

            $idRegra = $dados['idcuponregra'] ?? null;

            if (!empty($idRegra)) {
               // Atualiza a regra informada, se existir; caso contrário insere nova
               $getRegra = Cupon_regra::select()
                  ->where('idcuponregra', $idRegra)
                  ->where('idempresa', $dados['idempresa'])
                  ->one();

               if ($getRegra) {
                  Cupon_regra::update([
                     'valor'              => $valor       ?? $getRegra['valor'],
                     'descricao'          => $descricao   ?? $getRegra['descricao'],
                     'quantidade_pedidos' => $qtdPedidos  ?? $getRegra['quantidade_pedidos'],
                     'valor_minimo'       => $valorMinimo ?? $getRegra['valor_minimo']
                  ])
                     ->where('idcuponregra', $idRegra)
                     ->where('idempresa', $dados['idempresa'])
                     ->execute();
               } else {
                  Cupon_regra::insert([
                     'idempresa'          => $dados['idempresa'],
                     'valor'              => $valor       ?? 0,
                     'descricao'          => $descricao   ?? '',
                     'quantidade_pedidos' => $qtdPedidos  ?? 0,
                     'valor_minimo'       => $valorMinimo ?? 0,
                     'status'             => 1
                  ])->execute();
               }
            } else {
               // Sem id informado: tenta localizar regra existente por empresa; atualiza ou cria
               $existe = Cupon_regra::select()
                  ->where('idempresa', $dados['idempresa'])
                  ->one();

               if ($existe) {
                  Cupon_regra::update([
                     'valor'              => $valor       ?? $existe['valor'],
                     'descricao'          => $descricao   ?? $existe['descricao'],
                     'quantidade_pedidos' => $qtdPedidos  ?? $existe['quantidade_pedidos'],
                     'valor_minimo'       => $valorMinimo ?? $existe['valor_minimo']
                  ])
                     ->where('idcuponregra', $existe['idcuponregra'])
                     ->where('idempresa', $dados['idempresa'])
                     ->execute();
               } else {
                  Cupon_regra::insert([
                     'idempresa'          => $dados['idempresa'],
                     'valor'              => $valor       ?? 0,
                     'descricao'          => $descricao   ?? '',
                     'quantidade_pedidos' => $qtdPedidos  ?? 0,
                     'valor_minimo'       => $valorMinimo ?? 0,
                     'status'             => 1
                  ])->execute();
               }
            }
         }

         $db->commit();
         return ['status' => true, 'message' => 'Dados atualizados com sucesso.'];
      } catch (\Exception $e) {
         $db->rollBack();
         return ['status' => false, 'message' => $e->getMessage()];
      }
   }

   /**
    * Gera cupons a partir de pedidos válidos + selos extras
    * consumindo apenas o necessário para cada cupom.
    */
   public static function validarCuponPendentes(): array
   {
      $cuponsGerados = [];

      foreach (Empresa::select()->get() as $empresa) {

         /* parâmetro global de cupom ativo? */
         $ativo = Empresa_parametro::select()
            ->where('idempresa', $empresa['idempresa'])
            ->where('idparametro', 11)
            ->one();

         if (empty($ativo) || $ativo['valor'] === 'false') {
            continue;
         }

         /* percorre as regras da empresa */
         $regras = Cupon_regra::select()->where('idempresa', $empresa['idempresa'])->get();
         if (empty($regras)) {
            continue; // não há regras de cupom para esta empresa
         }
         foreach ($regras as $regra) {
            $tamanhoRegra = (int) $regra['quantidade_pedidos'];

            $clientes = Cupon::getClientesEleitos(
               $empresa['idempresa'],
               $tamanhoRegra,
               $regra['valor_minimo']
            );

            if (empty($clientes)) {
               continue; // não há clientes válidos para esta regra
            }

            foreach ($clientes as $cliente) {

               /* quanto preciso gerar? (já vem calculado na query) */
               $qtdCupons = (int) $cliente['cupons_gerados'];
               if ($qtdCupons <= 0) continue;

               /* separa tokens ► pedidos reais primeiro, depois selos extra */
               $tokens = [];

               if (!empty($cliente['pedidos_ids'])) {
                  foreach (explode(',', $cliente['pedidos_ids']) as $idPed) {
                     $tokens[] = ['tipo' => 'pedido', 'valor' => trim($idPed)];
                  }
               }

               if (!empty($cliente['extra_ids'])) {
                  foreach (explode(',', $cliente['extra_ids']) as $idSel) {
                     $tokens[] = ['tipo' => 'selo', 'valor' => trim($idSel)];
                  }
               }

               /* gera cada cupom, consumindo exatamente $tamanhoRegra tokens */
               for ($i = 0; $i < $qtdCupons; $i++) {

                  $lote = array_splice($tokens, 0, $tamanhoRegra);   // pega N itens
                  if (count($lote) < $tamanhoRegra) break;           // segurança

                  $idCupom = CuponModel::insert([
                     'idempresa' => $empresa['idempresa'],
                     'celular'   => $cliente['celular'],
                     'valor'     => $regra['valor'],
                     'descricao' => $regra['descricao'],
                     'idusuario' => 9999
                  ])->execute();

                  $selosConsumidos = [];

                  /* grava vínculos pedido ⇢ cupom | marca selos */
                  foreach ($lote as $tok) {
                     if ($tok['tipo'] === 'pedido') {
                        Cupon_pedidos::insert([
                           'idempresa'     => $empresa['idempresa'],
                           'idcupon'       => $idCupom,
                           'idpedidovenda' => $tok['valor']
                        ])->execute();
                     } else {
                        $selosConsumidos[] = $tok['valor'];
                     }
                  }

                  if ($selosConsumidos) {
                     Cupon_extra::update()
                        ->set(['utilizado' => 1])
                        ->whereIn('idextra', $selosConsumidos)
                        ->execute();
                  }

                  $cuponsGerados[] = $cliente['celular'];
               }
            }
         }
      }

      return $cuponsGerados;
   }


   /**
    * corverte data para o formato BRL date ou data hora
    */
   public static function formatarData($data, $hora = false)
   {
      if (empty($data)) {
         return '';
      }

      // Verifica se a data já está no formato correto
      if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
         return $data;
      }

      // Converte a data para o formato brasileiro
      $date = date_create($data);
      if (!$date) {
         return ''; // Retorna vazio se a data não for válida
      }

      $format = 'd/m/Y';
      if ($hora) {
         $format .= ' H:i:s';
      }

      return date_format($date, $format);
   }

   /**
    * Formata telefone para o formato brasileiro, ajustando com ou sem DDD.
    *
    * @param string $telefone Número de telefone a ser formatado.
    * @return string Telefone formatado ou o original se não for possível formatar.
    */
   public static function formatarTelefone($telefone)
   {
      // Remove caracteres não numéricos
      $telefone = preg_replace('/\D/', '', $telefone);

      // Verifica se o telefone tem 11 dígitos (formato celular com DDD)
      if (strlen($telefone) === 11) {
         return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
      }

      // Verifica se o telefone tem 10 dígitos (formato fixo com DDD)
      if (strlen($telefone) === 10) {
         return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
      }

      // Verifica se o telefone tem 9 dígitos (formato celular sem DDD)
      if (strlen($telefone) === 9) {
         return substr($telefone, 0, 5) . '-' . substr($telefone, 5);
      }

      // Verifica se o telefone tem 8 dígitos (formato fixo sem DDD)
      if (strlen($telefone) === 8) {
         return substr($telefone, 0, 4) . '-' . substr($telefone, 4);
      }

      // Retorna o telefone original se não estiver no formato esperado
      return $telefone;
   }
}

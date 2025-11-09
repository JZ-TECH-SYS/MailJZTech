<?php

namespace src\controllers;

use core\Controller as ctrl;
use src\handlers\Emails as EmailsHandler;
use src\handlers\Sistemas as SistemasHandler;

/**
 * Controller para gerenciar operações de e-mail
 * Segue arquitetura: Controller → Handler → Service → Model
 *
 * @author MailJZTech
 * @date 2025-01-09
 */
class EmailController extends ctrl
{
    public function index()
    {
        $this->render('emails');
    }

    /**
     * Envia um e-mail
     * POST /api/emails/send
     *
     * Body:
     * {
     *   "idsistema": 1,
     *   "destinatario": "usuario@exemplo.com",
     *   "assunto": "Título do e-mail",
     *   "corpo_html": "<h1>HTML</h1>",
     *   "corpo_texto": "Texto alternativo",
     *   "cc": "cc@exemplo.com", // opcional
     *   "bcc": "bcc@exemplo.com", // opcional
     *   "anexos": [] // opcional
     * }
     *
     * @return void
     */
    public function sendEmail()
    {
        try {
            // Obter dados do body
            $dados = ctrl::getBody(true);

            $dados['idsistema'] = $dados['idsistema'] ?? ctrl::getToken();

            // Validar campos obrigatórios
            ctrl::verificarCamposVazios($dados, ['idsistema', 'destinatario', 'assunto']);

            // Verificar se corpo_html ou corpo_texto existe
            if (empty($dados['corpo_html']) && empty($dados['corpo_texto'])) {
                throw new \Exception('corpo_html ou corpo_texto é obrigatório');
            }

            // Validação leve do sistema via handler (sem acessar model direto)
            if (!SistemasHandler::existeId($dados['idsistema'])) {
                throw new \Exception('Sistema não encontrado');
            }

            $sistema = SistemasHandler::obterPorId($dados['idsistema']);
            $idusuario = $_SESSION['user']['idusuario'] ?? 0;

            // Chamar handler (Controller → Handler)
            $resultado = EmailsHandler::enviar(
                $sistema['idsistema'],
                $idusuario,
                $dados
            );

            // Retornar resultado
            if ($resultado['sucesso']) {
                ctrl::response($resultado, 200);
            } else {
                ctrl::response($resultado, 400);
            }

        } catch (\Exception $e) {
            ctrl::log("Erro em sendEmail: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Lista e-mails enviados de um sistema (ou todos se não informado)
     * GET /listarEmails?idsistema=1&limite=50&pagina=1&status=enviado
     *
     * @return void
     */
    public function listarEmails()
    {
        try {
            // Obter parâmetros da query string
            $idsistema = filter_input(INPUT_GET, 'idsistema', FILTER_VALIDATE_INT);
            $limite = (int)($_GET['limite'] ?? 20);
            $pagina = (int)($_GET['pagina'] ?? 1);
            $status = $_GET['status'] ?? null;
            
            $offset = ($pagina - 1) * $limite;

            // Se sistema informado, validar existência
            if (!empty($idsistema) && !SistemasHandler::existeId($idsistema)) {
                throw new \Exception('Sistema não encontrado');
            }

            // Chamar handler (Controller → Handler) - aceita null para listar todos
            $emails = EmailsHandler::listar($idsistema, $limite, $offset);
            $total = EmailsHandler::contar($idsistema);
            
            // Aplicar filtro de status se informado (pós-processamento simples)
            if (!empty($status)) {
                $emails = array_filter($emails, function($e) use ($status) {
                    return $e['status'] === $status;
                });
                $emails = array_values($emails); // Reindexar
            }

            // Calcular total de páginas
            $totalPaginas = $total > 0 ? ceil($total / $limite) : 1;
            
            // Obter estatísticas para os cards
            $stats = EmailsHandler::obterEstatisticas($idsistema);

            // Retornar resultado
            ctrl::response([
                'emails' => $emails,
                'total' => $total,
                'enviados' => (int)($stats['enviados'] ?? 0),
                'erros' => (int)($stats['erros'] ?? 0),
                'pagina_atual' => $pagina,
                'paginas_totais' => $totalPaginas,
                'limite' => $limite
            ], 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em listarEmails: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém detalhes de um e-mail específico
     * GET /api/emails/obter?idemail=123
     *
     * @return void
     */
    public function obterEmail()
    {
        try {
            // Obter ID do e-mail e sistema
            $idemail = $_GET['idemail'] ?? null;
            $idsistema = $_GET['idsistema'] ?? null;

            if (!$idemail) {
                throw new \Exception('idemail é obrigatório');
            }

            if (!$idsistema) {
                throw new \Exception('idsistema é obrigatório');
            }

            // Chamar handler (Controller → Handler)
            $email = EmailsHandler::obter($idemail, $idsistema);

            if (!$email) {
                ctrl::response(['mensagem' => 'E-mail não encontrado'], 404);
                return;
            }

            // Retornar resultado
            ctrl::response($email, 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em obterEmail: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém estatísticas de e-mails
     * GET /api/emails/estatisticas?idsistema=1
     *
     * @return void
     */
    public function obterEstatisticas()
    {
        try {
            // Obter parâmetros
            $idsistema = $_GET['idsistema'] ?? null;

            if (!$idsistema) {
                throw new \Exception('idsistema é obrigatório');
            }

            // Chamar handler (Controller → Handler)
            $estatisticas = EmailsHandler::obterEstatisticas($idsistema);

            // Retornar resultado
            ctrl::response($estatisticas, 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em obterEstatisticas: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Testa configuração de e-mail
     * POST /api/emails/testar
     *
     * Body:
     * {
     *   "email_teste": "teste@exemplo.com"
     * }
     *
     * @return void
     */
    public function testarConfiguracao()
    {
        try {
            // Obter dados do body
            $dados = ctrl::getBody(true);

            // Validar campo
            ctrl::verificarCamposVazios($dados, ['email_teste']);

            // Obter ID do usuário da sessão/token
            $idusuario = $_SESSION['user']['idusuario'] ?? 0;

            // Chamar handler (Controller → Handler)
            $resultado = EmailsHandler::testar($dados['email_teste'], $idusuario);

            // Retornar resultado
            if ($resultado['sucesso']) {
                ctrl::response($resultado, 200);
            } else {
                ctrl::response($resultado, 400);
            }

        } catch (\Exception $e) {
            ctrl::log("Erro em testarConfiguracao: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Valida configuração SMTP
     * GET /api/emails/validar-configuracao
     *
     * @return void
     */
    public function validarConfiguracao()
    {
        try {
            // Chamar handler (Controller → Handler)
            $resultado = EmailsHandler::validarConfiguracao();

            // Retornar resultado
            if ($resultado['valido']) {
                ctrl::response($resultado, 200);
            } else {
                ctrl::response($resultado, 400);
            }

        } catch (\Exception $e) {
            ctrl::log("Erro em validarConfiguracao: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Detalhe de e-mail (API estilo REST via rota /detalheEmail/{idemail})
     * GET /detalheEmail/{idemail}
     * Usa handler para buscar e valida pertencimento ao sistema se idsistema informado
     */
    public function detalheEmail($args = [])
    {
        try {
            $idemail = $args['id'] ?? $args['idemail'] ?? null;
            if (!$idemail) {
                throw new \Exception('ID do e-mail não informado');
            }

            // Sistema opcional via query (?idsistema=) para validar pertencimento; se não vier, busca geral
            $idsistemaQS = filter_input(INPUT_GET, 'idsistema', FILTER_VALIDATE_INT);
            $idsistema = $idsistemaQS ?: null;

            // Se sistema informado, valida existência
            if (!empty($idsistema) && !\src\handlers\Sistemas::existeId($idsistema)) {
                throw new \Exception('Sistema não encontrado');
            }

            // Handler obter exige idsistema para validar pertencimento; se não informado, fazemos busca simples direta
            $email = null;
            if (!empty($idsistema)) {
                $email = EmailsHandler::obter($idemail, $idsistema);
            } else {
                // Busca sem validação de sistema: usamos model diretamente via handler adaptado (criamos método interno)
                $emailModel = \src\models\Emails_enviados::getById($idemail);
                $email = $emailModel ?: false;
            }

            if (!$email) {
                ctrl::response(['mensagem' => 'E-mail não encontrado'], 404);
                return;
            }

            ctrl::response($email, 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}

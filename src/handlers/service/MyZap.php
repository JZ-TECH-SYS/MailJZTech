<?php

namespace src\handlers\service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use src\Config;

class MyZap
{
    const BASE = Config::API_MYZAP;
    const API_TOKEN = Config::API_MYZAP_TOKEN;


    private static function client(): Client
    {
        return new Client(['timeout' => 15]);
    }

    /**
     * Método genérico para POST com sessionkey no header e session no payload
     */
    private static function post(string $sessionKey, string $endpoint, array $data): ?array
    {
        try {
            $response = self::client()->post(self::BASE . $endpoint, [
                'headers' => [
                    'sessionkey' => $sessionKey,
                    'apitoken' => self::API_TOKEN
                ],
                'json' => $data
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : null;
            $decoded = null;
            if ($body && str_starts_with(trim($body), '{')) {
                $decoded = json_decode($body, true);
            }

            return [
                'error' => true,
                'message' => 'Erro na requisição à API MyZap',
                'details' => $e->getMessage(),
                'status_code' => $e->getResponse()?->getStatusCode(),
                'response' => $body,
                'status' => $decoded['status'] ?? null,
                'messages' => $decoded['messages'] ?? null,
                'result' => $decoded['result'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Erro inesperado ao comunicar com MyZap',
                'details' => $e->getMessage()
            ];
        }
    }



    /**
     * Verifica se a sessão está conectada
     *
     * @param string $session
     * @param string $sessionKey
     * @throws \Exception
     */
    private static function checkConnected(string $session, string $sessionKey): void
    {
        $res = self::getConnectionStatus($session, $sessionKey);
        $status = $res['status'] ?? null;
        if (!in_array($status, ['inChat', 'CONNECTED', 'isLogged', 'isConnected'])) {
            throw new \Exception("WhatsApp não está conectado. Informe ao Estabelecimento!.", 400);
        }
    }


    /**
     * Verifica status da sessão
     *
     * @param string $session
     * @param string $sessionKey
     * @return array|null
     *
     * 📦 Exemplo de uso:
     * MyZap::getConnectionStatus('empresaABC', 'abc123');
     *
     * 📤 Payload enviado:
     * {
     *   "session": "empresaABC"
     * }
     *
     * ✅ Exemplo de resposta:
     * {
     *   "result": "success",
     *   "session": "empresaABC",
     *   "status": "inChat"
     * }
     */
    public static function getConnectionStatus(string $session, string $sessionKey): ?array
    {
        return self::post($sessionKey, '/getConnectionStatus', ['session' => $session]);
    }

    /**
     * Solicita QRCode (se não estiver conectado)
     *
     * @param string $session
     * @param string $sessionKey
     * @return array|null
     *
     * 📦 Exemplo de uso:
     * MyZap::getQrCode('empresaABC', 'abc123');
     *
     * 📤 Payload enviado:
     * {
     *   "session": "empresaABC"
     * }
     *
     * ✅ Exemplo de resposta:
     * {
     *   "state": "QRCODE",
     *   "qrcode": "data:image/png;base64,..."
     * }
     */
    public static function Start(string $session, string $sessionKey): ?array
    {
        return self::post($sessionKey, '/start', ['session' => $session]);
    }

    /**
     * Desconecta e remove a sessão
     *
     * @param string $session
     * @param string $sessionKey
     * @return array|null
     *
     * 📦 Exemplo de uso:
     * MyZap::deleteSession('empresaABC', 'abc123');
     *
     * ✅ Resposta esperada:
     * {
     *   "result": "success",
     *   "message": "Sessão encerrada com sucesso"
     * }
     */
    public static function deleteSession(string $session, string $sessionKey): ?array
    {
        return self::post($sessionKey, '/deleteSession', ['session' => $session]);
    }


    /**
     * Envia uma mensagem de texto
     *
     * @param string $session
     * @param string $sessionKey
     * @param string $numero - Ex: 5511999999999
     * @param string $mensagem
     * @return array|null
     *
     * 📦 Exemplo de uso:
     * MyZap::sendText('empresaABC', 'abc123', '5511999999999', 'Olá, tudo bem?');
     *
     * 📤 Payload enviado:
     * {
     *   "session": "empresaABC",
     *   "number": "5511999999999",
     *   "text": "Olá, tudo bem?"
     * }
     *
     * ✅ Exemplo de resposta:
     * {
     *   "result": 200,
     *   "data": {
     *     "id": "...",
     *     "body": "Olá, tudo bem?"
     *   }
     * }
     */
    public static function sendText(string $session, string $sessionKey, string $numero, string $mensagem): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendText', [
            'session' => $session,
            'number' => $numero,
            'text' => $mensagem
        ]);
    }


    /**
     * Envia uma imagem por URL com legenda
     *
     * @param string $session
     * @param string $sessionKey
     * @param string $numero
     * @param string $url - URL da imagem
     * @param string $caption - Legenda opcional
     * @return array|null
     *
     * 📦 Exemplo de uso:
     * MyZap::sendImage('empresaABC', 'abc123', '5511999999999', 'https://site.com/img.jpg', 'Veja isso');
     *
     * 📤 Payload enviado:
     * {
     *   "session": "empresaABC",
     *   "number": "5511999999999",
     *   "path": "https://site.com/img.jpg",
     *   "caption": "Veja isso"
     * }
     *
     * ✅ Exemplo de resposta:
     * {
     *   "result": 200,
     *   "type": "image",
     *   "messageId": "msg123456",
     *   "session": "empresaABC",
     *   "mimetype": "image/jpeg"
     * }
     */
    public static function sendImage(string $session, string $sessionKey, string $numero, string $url, string $caption = ''): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendImage', [
            'session' => $session,
            'number' => $numero,
            'path' => $url,
            'caption' => $caption
        ]);
    }



    /**
     * Envia arquivo por URL (PDF, DOC, ZIP, etc)
     *
     * @param string $session
     * @param string $sessionKey
     * @param string $numero
     * @param string $url - URL direta do arquivo
     * @param string $nome - Nome do arquivo (opcional)
     * @return array|null
     *
     * 📦 Exemplo:
     * MyZap::sendFile('empresaABC', 'abc123', '5511999999999', 'https://site.com/relatorio.pdf', 'relatorio.pdf');
     */
    public static function sendFile(string $session, string $sessionKey, string $numero, string $url, string $nome = 'arquivo'): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendFile', [
            'session' => $session,
            'number' => $numero,
            'path' => $url,
            'options' => ['filename' => $nome]
        ]);
    }

    /**
     * Envia um arquivo em Base64
     *
     * @param string $session
     * @param string $sessionKey
     * @param string $numero
     * @param string $base64 - "data:application/pdf;base64,..."
     * @param string $nome
     * @return array|null
     *
     * 📦 Exemplo:
     * MyZap::sendFile64('empresaABC', 'abc123', '5511999999999', 'data:application/pdf;base64,SUQz...', 'boleto.pdf');
     */
    public static function sendFile64(string $session, string $sessionKey, string $numero, string $base64, string $nome): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendFile64', [
            'session' => $session,
            'number' => $numero,
            'path' => $base64,
            'caption' => $nome
        ]);
    }


    /**
     * Envia um áudio por URL
     *
     * @param string $session
     * @param string $sessionKey
     * @param string $numero
     * @param string $url - Deve ser .mp3, .ogg ou .webm
     * @return array|null
     *
     * 📦 Exemplo:
     * MyZap::sendAudio('empresaABC', 'abc123', '5511999999999', 'https://site.com/audio.mp3');
     */
    public static function sendAudio(string $session, string $sessionKey, string $numero, string $url): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendAudio', [
            'session' => $session,
            'number' => $numero,
            'path' => $url
        ]);
    }


    /**
     * Envia localização geográfica
     *
     * @param string $session
     * @param string $sessionKey
     * @param string $numero
     * @param float $lat
     * @param float $lng
     * @param string $title
     * @param string $description
     *
     * 📦 Exemplo:
     * MyZap::sendLocation('empresaABC', 'abc123', '5511999999999', -23.5, -46.6, 'Escritório', 'Av. Paulista 123');
     */
    public static function sendLocation(string $session, string $sessionKey, string $numero, float $lat, float $lng, string $title, string $description): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendLocation', [
            'session' => $session,
            'number' => $numero,
            'lat' => $lat,
            'log' => $lng,
            'title' => $title,
            'description' => $description
        ]);
    }

    /**
     * Envia link com preview
     *
     * 📦 Exemplo:
     * MyZap::sendLink('empresaABC', 'abc123', '5511999999999', 'https://meusite.com', 'Veja nosso site!');
     */
    public static function sendLink(string $session, string $sessionKey, string $numero, string $url, string $text): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendLink', [
            'session' => $session,
            'number' => $numero,
            'url' => $url,
            'text' => $text
        ]);
    }

    /**
     * Envia contato em formato vCard
     *
     * 📦 Exemplo:
     * MyZap::sendContact('empresaABC', 'abc123', '5511999999999', '5511988888888', 'João Zap');
     */
    public static function sendContact(string $session, string $sessionKey, string $numero, string $contato, string $nome): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendContact', [
            'session' => $session,
            'number' => $numero,
            'contact' => $contato,
            'name' => $nome
        ]);
    }

    /**
     * Envia reação para uma mensagem
     *
     * 📦 Exemplo:
     * MyZap::sendReaction('empresaABC', 'abc123', 'msg123456', '❤️');
     */
    public static function sendReaction(string $session, string $sessionKey, string $messageId, string $emoji): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/reaction', [
            'session' => $session,
            'messageId' => $messageId,
            'emoji' => $emoji
        ]);
    }


    /**
     * Envia enquete (poll)
     *
     * 📦 Exemplo:
     * MyZap::sendPoll('empresaABC', 'abc123', '5511999999999', 'Qual sabor?', ['Tereré', 'Chimarrão']);
     */
    public static function sendPoll(string $session, string $sessionKey, string $numero, string $pergunta, array $opcoes): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendPoll', [
            'session' => $session,
            'number' => $numero,
            'name' => $pergunta,
            'choices' => $opcoes
        ]);
    }

    /**
     * Envia lista com seções e botões
     *
     * 📦 Exemplo:
     * MyZap::sendList('empresaABC', 'abc123', '5511999999999', 'Escolha um produto', [
     *   [
     *     "title" => "Ervas",
     *     "rows" => [
     *       ["title" => "Tradicional", "description" => "100g"],
     *       ["title" => "Menta", "description" => "120g"]
     *     ]
     *   ]
     * ]);
     */
    public static function sendList(string $session, string $sessionKey, string $numero, string $descricao, array $sections, string $botao = 'SELECIONE'): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendList', [
            'session' => $session,
            'number' => $numero,
            'description' => $descricao,
            'sections' => $sections,
            'buttonText' => $botao
        ]);
    }

    /**
     * Envia vários arquivos por URL (um por vez internamente)
     *
     * @param string $session
     * @param string $sessionKey
     * @param string $numero
     * @param array $arquivos Lista com ['path' => link, 'filename' => nome opcional]
     *
     * @return array|null
     *
     * 📤 Exemplo de envio:
     * MyZap::sendMultipleFiles('empresaABC', 'abc123', '5511999999999', [
     *     ['path' => 'https://meu.site/arquivo1.pdf', 'filename' => 'nota1.pdf'],
     *     ['path' => 'https://meu.site/arquivo2.xlsx', 'filename' => 'relatorio.xlsx']
     * ]);
     *
     * 📥 Exemplo de resposta:
     * [
     *   'result' => 200,
     *   'session' => 'empresaABC',
     *   'number' => '5511999999999',
     *   'total' => 2,
     *   'files' => [
     *     ['path' => '...', 'filename' => '...', 'messageId' => 'ABC123@whatsapp.net', 'success' => true],
     *     ['path' => '...', 'filename' => '...', 'messageId' => 'DEF456@whatsapp.net', 'success' => true]
     *   ]
     * ]
     */
    public static function sendMultipleFiles(string $session, string $sessionKey, string $numero, array $arquivos): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendMultipleFiles', [
            'session' => $session,
            'number' => $numero,
            'files' => $arquivos
        ]);
    }


    /**
     * Envia vários arquivos em Base64 (um por vez internamente)
     *
     * @param string $session
     * @param string $sessionKey
     * @param string $numero
     * @param array $arquivos Lista com ['base64' => 'data:...', 'filename' => ..., 'caption' => ...]
     *
     * @return array|null
     *
     * 📤 Exemplo de envio:
     * MyZap::sendMultipleFile64('empresaABC', 'abc123', '5511999999999', [
     *     ['base64' => 'data:application/pdf;base64,JVBER...', 'filename' => 'boleto.pdf', 'caption' => 'Segue o boleto'],
     *     ['base64' => 'data:image/png;base64,iVBOR...', 'filename' => 'img.png', 'caption' => 'Comprovante']
     * ]);
     *
     * 📥 Exemplo de resposta:
     * [
     *   'result' => 200,
     *   'session' => 'empresaABC',
     *   'number' => '5511999999999',
     *   'total' => 2,
     *   'files' => [
     *     ['filename' => 'boleto.pdf', 'caption' => '...', 'messageId' => '...', 'success' => true],
     *     ['filename' => 'img.png', 'caption' => '...', 'messageId' => '...', 'success' => true]
     *   ]
     * ]
     */
    public static function sendMultipleFile64(string $session, string $sessionKey, string $numero, array $arquivos): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/sendMultipleFile64', [
            'session' => $session,
            'number' => $numero,
            'files' => $arquivos
        ]);
    }

    /**
     * Atualiza configuração da IA no MyZap
     *
     * @param string $session
     * @param string $sessionKey
     * @param string $mensagemPadrao
     * @return array|null
     *
     * 📦 Exemplo de uso:
     * MyZap::updateIaConfig('empresaABC', 'abc123', 'Mensagem padrão aqui');
     *
     * 📤 Payload enviado:
     * {
     *   "session": "empresaABC",
     *   "sessionkey": "abc123",
     *   "mensagem_padrao": "Mensagem padrão aqui"
     * }
     */
    public static function updateIaConfig(string $session, string $sessionKey, string $mensagemPadrao, string $apiUrl): ?array
    {
        self::checkConnected($session, $sessionKey);
        return self::post($sessionKey, '/admin/ia-manager/update-config', [
            'session' => $session,
            'sessionkey' => $sessionKey,
            'mensagem_padrao' => $mensagemPadrao,
            'api_url' => $apiUrl ?? null
        ]);
    }
}

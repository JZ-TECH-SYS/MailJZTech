<?php

namespace src\handlers\service;

use core\Controller;
use Google\Cloud\Storage\StorageClient;
use Exception;
use Psr\Http\Message\StreamInterface;

/**
 * Classe de serviço para integração com o Google Cloud Storage.
 * Ajuste os caminhos de arquivo e bucket conforme sua necessidade.
 */
class GoogleCloud
{
    /**
     * Retorna uma instância de StorageClient para acessar o GCS.
     */
    private static function getStorageClient()
    {
        $keyPath = __DIR__ . '/bkp.json';
        if (!file_exists($keyPath)) {
            throw new Exception("Credenciais do GCS não encontradas em {$keyPath}. Certifique-se de que o deploy gerou o arquivo bkp.json.");
        }

        return new StorageClient([
            'keyFilePath' => $keyPath
        ]);
    }

    /**
     * Faz upload de um arquivo para o Google Cloud Storage.
     *
     * @param array  $file       Dados do arquivo (ex.: $_FILES['arquivo'])
     * @param string $bucketName Nome do bucket no GCS
     * @param string $objectName Caminho/nome do objeto dentro do bucket (opcional)
     *
     * @return array Informações do objeto no GCS (mediaLink, name, etc.)
     */
    public static function uploadFile(array $file, string $bucketName, string $objectName = '')
    {
        if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            throw new Exception("Arquivo inválido ou não encontrado em tmp_name");
        }

        $storage = self::getStorageClient();
        $bucket = $storage->bucket($bucketName);

        // Gera um nome único para o arquivo no GCS
        if (empty($objectName)) {
            $objectName = uniqid('file_') . '.' . pathinfo($file['name'], PATHINFO_EXTENSION); // Gera um nome único
        }

        // Faz o upload para o bucket
        $object = $bucket->upload(
            fopen($file['tmp_name'], 'r'),
            [
                'name' => $objectName
            ]
        );

        // Retorna as informações do objeto, incluindo nome
        return $object->info();
    }

    /**
     * Gera uma URL assinada para acessar um arquivo privado.
     *
     * @param string $bucketName Nome do bucket
     * @param string $objectName Nome do objeto no bucket
     * @param int    $expires    Tempo de expiração da URL (em segundos)
     *
     * @return string URL temporária para acessar o arquivo
     */
    public static function generateSignedUrl(string $bucketName, string $objectName, int $expires = 3600)
    {
        $storage = self::getStorageClient();
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->object($objectName);

        // Gera uma URL assinada
        $url = $object->signedUrl(
            new \DateTime('+' . $expires . ' seconds'), // Expiração após $expires segundos
            [
                'version' => 'v4', // versão 4 da URL assinada
                'method'  => 'GET', // método HTTP (pode ser GET ou PUT, dependendo do uso)
            ]
        );

        return $url;
    }

    /**
     * Retorna informações de um arquivo (objeto) no GCS.
     *
     * @param string $bucketName Nome do bucket
     * @param string $objectName Caminho/nome do objeto no bucket
     *
     * @return array|null Informações do objeto ou null se não existir
     */
    public static function getFileInfo(string $bucketName, string $objectName)
    {
        $storage = self::getStorageClient();
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->object($objectName);

        if (!$object->exists()) {
            return null;
        }

        return $object->info();
    }

    /**
     * Faz o download de um arquivo do GCS para um destino local.
     *
     * @param string $bucketName   Nome do bucket
     * @param string $objectName   Caminho/nome do objeto no bucket
     * @param string $destination  Caminho local para salvar o arquivo
     *
     * @return void
     */
    public static function downloadFile(string $bucketName, string $objectName)
    {
        // Usa caminho relativo seguro e compatível com Linux/Windows
        $folder = __DIR__ . './../../../public/ARQUIVOS'; // ajusta conforme sua estrutura
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $destination = $folder . '/' . basename($objectName);
        if (!file_exists($destination)) {
            return; // Arquivo já existe, não faz download
        }

        $storage = self::getStorageClient();
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->object($objectName);

        if (!$object->exists()) {
            throw new Exception("Objeto não encontrado no GCS: $objectName");
        }

        $object->downloadToFile($destination);
    }


    /**
     * Faz o download de um arquivo do GCS e retorna como stream.
     *
     * @param string $bucketName Nome do bucket
     * @param string $objectName Caminho/nome do objeto no bucket
     *
     * @return resource Stream do arquivo
     */
    public static function downloadFileAsStream(string $bucketName, string $objectName)
    {
        $storage = self::getStorageClient();
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->object($objectName);

        if (!$object->exists()) {
            throw new Exception("Objeto não encontrado no GCS: $objectName");
        }

        // Pega o conteúdo do arquivo como stream
        return $object->downloadAsStream();
    }

    /**
     * Faz o download de um arquivo do GCS e retorna como string.
     */
    public static function downloadFileAsString(string $bucket, string $object): string
    {
        $storage = self::getStorageClient();
        $obj     = $storage->bucket($bucket)->object($object);

        if (!$obj->exists()) {
            throw new \Exception("Objeto $object não encontrado no bucket $bucket");
        }

        $stream = $obj->downloadAsStream();   // Psr7\Stream

        // ‼️  Se for um StreamInterface (caso padrão)
        if ($stream instanceof StreamInterface) {
            $stream->rewind();                // garante ponteiro no início
            return $stream->getContents();    // ✅ string binária do arquivo
        }

        // Fallback raro: se algum dia vier como resource
        if (is_resource($stream)) {
            rewind($stream);
            return stream_get_contents($stream);
        }

        throw new \Exception("Tipo de stream inesperado ao baixar $object");
    }




    /**
     * Exclui um arquivo (objeto) do GCS.
     *
     * @param string $bucketName Nome do bucket
     * @param string $objectName Caminho/nome do objeto no bucket
     *
     * @return void
     */
    public static function deleteFile(string $bucketName, string $objectName)
    {
        $storage = self::getStorageClient();
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->object($objectName);

        if ($object->exists()) {
            $object->delete();
        }
    }

    /**
     * Lista todos os objetos em um prefixo do bucket que são mais antigos que um timestamp.
     *
     * @param string $bucketName Nome do bucket
     * @param string $prefix Prefixo (pasta) para filtrar objetos
     * @param int $olderThanTimestamp Timestamp limite - objetos mais antigos serão retornados
     *
     * @return array Lista de objetos com name e timeCreated
     */
    public static function listObjectsOlderThan(string $bucketName, string $prefix, int $olderThanTimestamp): array
    {
        $storage = self::getStorageClient();
        $bucket = $storage->bucket($bucketName);

        $options = [
            'prefix' => rtrim($prefix, '/') . '/'
        ];

        $objects = $bucket->objects($options);
        $result = [];

        foreach ($objects as $object) {
            $info = $object->info();
            
            // Pular se não for um arquivo de backup
            if (!preg_match('/\.sql\.gz$/', $info['name'])) {
                continue;
            }

            // Verificar data de criação
            $timeCreated = isset($info['timeCreated']) 
                ? strtotime($info['timeCreated']) 
                : null;

            if ($timeCreated && $timeCreated < $olderThanTimestamp) {
                $result[] = [
                    'name' => $info['name'],
                    'timeCreated' => $info['timeCreated'],
                    'size' => $info['size'] ?? 0
                ];
            }
        }

        return $result;
    }

    /**
     * Lista todos os objetos de backup em um prefixo do bucket.
     *
     * @param string $bucketName Nome do bucket
     * @param string $prefix Prefixo (pasta) para filtrar objetos
     * @param int $limite Número máximo de objetos a retornar (0 = sem limite)
     *
     * @return array Lista de objetos
     */
    public static function listBackupObjects(string $bucketName, string $prefix, int $limite = 0): array
    {
        $storage = self::getStorageClient();
        $bucket = $storage->bucket($bucketName);

        $options = [
            'prefix' => rtrim($prefix, '/') . '/'
        ];

        $objects = $bucket->objects($options);
        $result = [];
        $count = 0;

        foreach ($objects as $object) {
            $info = $object->info();
            
            // Filtrar apenas arquivos de backup
            if (!preg_match('/\.sql\.gz$/', $info['name'])) {
                continue;
            }

            $result[] = [
                'name' => $info['name'],
                'timeCreated' => $info['timeCreated'] ?? null,
                'size' => $info['size'] ?? 0,
                'md5Hash' => $info['md5Hash'] ?? null
            ];

            $count++;
            if ($limite > 0 && $count >= $limite) {
                break;
            }
        }

        // Ordenar por data de criação (mais recente primeiro)
        usort($result, function($a, $b) {
            return strtotime($b['timeCreated'] ?? '0') - strtotime($a['timeCreated'] ?? '0');
        });

        return $result;
    }
}

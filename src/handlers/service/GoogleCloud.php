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
        return new StorageClient([
            'keyFilePath' => __DIR__ . '/bkp.json'
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
}

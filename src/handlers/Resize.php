<?php

namespace src\handlers;

use Exception;

class Resize
{
    /**
     * Recebe uma imagem em base64, corrige a rotação e converte para arquivo WebP com compressão.
     * Inclui redimensionamento e otimização.
     * @param $base64_string - imagem em base64
     */
    public static function saveUploadedImage($file)
    {
        // Verifica se o arquivo é válido e se não há erros
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload da imagem.');
        }

        // Define o caminho para salvar a imagem no servidor
        $destinationPath = './../public/images/';
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        // Obtém o tipo de imagem enviado
        $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Corrige a orientação apenas para tipos suportados (JPEG e PNG)
        if (in_array($imageFileType, ['jpeg', 'jpg', 'png'])) {
            self::correctOrientation($file['tmp_name'], $imageFileType);
        }

        // Cria um nome único para a imagem
        $uniqueFileName = uniqid('image-', true) . '.webp';
        $destinationFile = $destinationPath . $uniqueFileName;

        // Converte a imagem para WebP e salva
        if (self::convertToWebPWithGD($file['tmp_name'], $destinationFile, $imageFileType)) {
            return $uniqueFileName;
        } else {
            throw new Exception('Erro ao converter a imagem para WebP.');
        }
    }

    /**
     * Converte uma imagem PNG/JPEG para o formato WebP usando GD e aplica compressão.
     * @param $sourcePath - caminho da imagem original (PNG ou JPEG)
     * @param $destinationPath - caminho onde o WebP será salvo
     * @param $imageType - tipo da imagem original (jpg, png, etc.)
     * @param $newWidth - nova largura desejada (mantém a proporção)
     * @param $quality - qualidade da imagem final (0-100)
     * @return bool - Retorna `true` se a conversão for bem-sucedida, `false` caso contrário.
     */
    public static function convertToWebPWithGD($sourcePath, $destinationPath, $imageType, $newWidth = 800, $quality = 75)
    {
        // Cria uma nova imagem de acordo com o tipo original
        switch ($imageType) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $image = imagecreatefrompng($sourcePath);
                break;
            default:
                throw new Exception('Tipo de imagem não suportado para conversão para WebP');
        }

        // Verifica se a imagem foi carregada corretamente
        if ($image === false) {
            throw new Exception('Erro ao carregar a imagem. O arquivo pode estar corrompido ou não é uma imagem válida.');
        }

        // Obtém informações da imagem original
        $largura_original = imagesx($image);
        $altura_original = imagesy($image);
        $ratio = $largura_original / $altura_original;

        // Define largura e altura novas, mantendo a proporção
        $newHeight = (int) ($newWidth / $ratio);

        // Cria a imagem redimensionada
        $nova_imagem = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($nova_imagem, $image, 0, 0, 0, 0, $newWidth, $newHeight, $largura_original, $altura_original);

        // Salva a imagem no formato WebP com a qualidade especificada
        $result = imagewebp($nova_imagem, $destinationPath, $quality);

        // Libera a memória
        imagedestroy($image);
        imagedestroy($nova_imagem);

        return $result;
    }

    /**
     * Corrige a orientação da imagem com base nos metadados EXIF para JPEG e rotação para PNG.
     * @param $filename - caminho para a imagem original
     * @param $imageType - tipo da imagem (jpeg, png)
     */
    public static function correctOrientation($filename, $imageType)
    {
        if (function_exists('exif_read_data') && in_array($imageType, ['jpeg', 'jpg'])) {
            $exif = @exif_read_data($filename);
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];

                // Cria a imagem com base no tipo
                $image = imagecreatefromjpeg($filename);

                switch ($orientation) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }

                // Salva a imagem com a orientação corrigida
                imagejpeg($image, $filename, 100);
                imagedestroy($image);
            }
        } elseif ($imageType === 'png') {
            // Aplicar ajustes para PNG, se necessário (por exemplo, rotação com base em cabeçalhos específicos)
            $image = imagecreatefrompng($filename);
            imagepng($image, $filename);
            imagedestroy($image);
        }
    }

    /**
     * Recebe uma imagem em base64 e verifica se é uma foto através dos primeiros bytes.
     * @param $base64_string - imagem em base64
     */
    public static function isImageData($base64_string)
    {
        $firstBytes = substr($base64_string, 0, 8);
        $hex = bin2hex($firstBytes);

        $magicNumbers = [
            'jpeg' => 'ffd8',
            'png'  => '89504e47',
            'jpg'  => 'ffd8',
        ];

        foreach ($magicNumbers as $magicNumber) {
            if (strpos($hex, $magicNumber) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica depois de converter a imagem em base64 se é um tipo de imagem suportado.
     * @param $imageData - imagem convertida
     */
    public static function isSupportedImageType($imageData)
    {
        $supportedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        $imageInfo = getimagesizefromstring($imageData);

        if ($imageInfo === false) {
            return false;
        }

        $mimeType = $imageInfo['mime'];

        return in_array($mimeType, $supportedTypes);
    }

    /**
     * remove uma imagem
     * @param $photo - nome da imagem
     */
    public static function deleteImage($photo)
    {
        if (!empty($photo)) {
            $filename = './../public/images/' . $photo;
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }
}

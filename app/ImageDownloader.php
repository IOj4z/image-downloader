<?php

namespace Laravel\app;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ImageDownloader
{
    // Метод для скачивания картинок с помощью потокового подхода
    public function downloadImagesFromApi($imageUrls)
    {
        $downloadedImages = [];

        // Создаем логгер Monolog
        $logger = new Logger('ImageDownloader');
        $logger->pushHandler(new StreamHandler('/path/to/log/image_downloader.log', Logger::ERROR));

        foreach ($imageUrls as $imageUrl) {
            try {
                // Используем библиотеку Guzzle для выполнения HTTP-запроса и скачивания картинки
                $client = new Client();

                // Открываем поток для записи данных картинки
                $imageName = md5($imageUrl) . '.jpg';
                $tempImagePath = '/path/to/temp/' . $imageName;
                $stream = fopen($tempImagePath, 'w');

                // Запускаем потоковое скачивание файла
                $response = $client->get($imageUrl, [
                    RequestOptions::SINK => $stream,
                    RequestOptions::TIMEOUT => 600, // Установим таймаут скачивания на 10 минут
                    RequestOptions::VERIFY => false, // Отключаем проверку SSL сертификата (для тестового кода)
                ]);

                // Проверяем статус код ответа
                $statusCode = $response->getStatusCode();
                if ($statusCode === 200) {
                    // Добавляем путь к временной картинке в массив скачанных картинок
                    $downloadedImages[] = $tempImagePath;

                    // Добавляем URL картинки в очередь для дальнейшей обработки
                    $imageQueue = new ImageQueue();
                    $imageQueue->addImageToQueue($imageUrl);
                } else {
                    // Обработка ошибок или некорректных ответов от API
                    $errorLog = sprintf(
                        'Failed to download image from URL: %s. Status Code: %d',
                        $imageUrl,
                        $statusCode
                    );
                    $logger->error($errorLog);
                }

                // Закрываем поток
                fclose($stream);
            } catch (\Exception $e) {
                // Обработка исключений, которые могут возникнуть при скачивании картинок
                $errorLog = sprintf(
                    'Exception occurred while downloading image from URL: %s. Error: %s',
                    $imageUrl,
                    $e->getMessage()
                );
                $logger->error($errorLog);
            }
        }

        return $downloadedImages;
    }
}

<?php


namespace App\Service\GoldIntern;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Команда для отправки запроса о действия с тудушкой на другой сервер
 *
 * Class SendRequest
 * @package App\Service\GoldIntern
 */
class SendRequest
{
    public static function run(int $userId, string $action): int
    {
        $client = new Client(
            [
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ]
        );

        try {
            $response = $client->post(
                $_ENV['REQUEST_SERVER'],
                [
                    'form_params' => [
                        'user' => $userId,
                        'action' => $action
                    ]
                ]
            );

            return $response->getStatusCode();
        } catch (GuzzleException $exception) {
            return 404;
        }
    }
}
<?php

if(!function_exists('logger'))
{

    /**
     * @param string $text
     * @param string $type INFO | WARNING | ERROR | CRITICAL
     * @param int|null $jobId
     */
    function logger(string $text, string $type = "INFO", ?int $jobId = null)
    {
        $pdo = \Kido\Services\DBConnector::init()->getPDO();

        $query = $pdo->prepare('insert into logs(jobId, logType, text) values(?, ?, ?)');
        $query->execute([$jobId, $type, $text]);
    }

}

if(!function_exists('telegramLog'))
{

    /**
     * @param string $text
     * @param string $type INFO | WARNING | ERROR | CRITICAL
     * @param int|null $jobId
     */
    function telegramLog(string $clientId, int $amount, int $currencyCode, string $nextPaymentDate)
    {

        $currencies = [
            643 => 'RUB',
            51 => 'AMD',
            840 => 'USD'
        ];

        $client = new \GuzzleHttp\Client();

        $url = 'https://api.telegram.org/bot1495646464:AAHDyL_nrxR7l28LsUlrRPiCnojdhMsAeio/sendMessage?';

        $params = [
            'chat_id' => '-1001193717632',
            'text' => '🔥Пользователь '.$clientId.' пожертвовал '.((float)$amount/100). ' ' .$currencies[$currencyCode] ." 💵 
-> Следующий платеж автоматически спишется " . $nextPaymentDate
        ];

        $client->get($url . http_build_query($params));
    }

}
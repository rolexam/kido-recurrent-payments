<?php


namespace Kido;


use Carbon\Carbon;
use Kido\Models\Job;
use Kido\Services\DBConnector;
use Kido\Services\JobProcessor;
use PDO;

class App
{

    private static $jobsLimit = 10;

    public static function run()
    {
        $pdo = DBConnector::init()->getPDO();
        $now = Carbon::now()->setTimezone('Europe/Moscow')->toDateTimeString();

        $stm = $pdo->prepare('select * from jobs where status = "ENABLED" and isProcessing = 0 and nextPaymentDate < :nextPaymentDate and attempts < 3 limit :limit');
        $stm->bindParam(':nextPaymentDate', $now, PDO::PARAM_STR);
        $stm->bindParam(':limit', self::$jobsLimit, PDO::PARAM_INT);

        $stm->execute();

        $data = $stm->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as $item) {
            JobProcessor::init($item)->process();
        }
    }
}

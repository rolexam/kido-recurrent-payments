<?php


namespace Kido\Services;


use Carbon\Carbon;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;

class JobProcessor
{
    protected array $job;

    protected Client $http;

    public function __construct(array $job)
    {
        $this->job = $job;

        $this->http = new Client([
            "base_uri" => "https://ipay.arca.am/"
        ]);
    }

    public static function init(array $job) : JobProcessor
    {
        return new self($job);
    }

    public function process() : void
    {
        // Set processing true | lock job
        $this->setProcessing(true);

        try {
            $order = $this->createOrder();
        } catch (\Exception $e) {
            $this->jobFailed($e->getMessage());
            $this->setProcessing(false);
            return;
        }

        if($order['errorCode'] != 0 || empty($order['orderId']))
        {
            $this->jobFailed(json_encode($order, JSON_UNESCAPED_UNICODE));
            $this->setProcessing(false);
            return;
        }


        try {
            $payment = $this->proceedPayment($order['orderId']);
        } catch (\Exception $e) {
            $this->jobFailed($e->getMessage());
            $this->setProcessing(false);
            return;
        }

        if($payment['errorCode'] != 0)
        {
            $this->jobFailed(json_encode($payment, JSON_UNESCAPED_UNICODE));
            $this->setProcessing(false);
            return;
        }

        logger("Successful payment", "INFO", $this->job['id']);

        $this->updateNextPaymentDate();

        telegramLog(
            $this->job['clientId'],
            $this->job['amount'],
            $this->job['currencyCode'],
            Carbon::parse($this->job['nextPaymentDate'])->addMonths($this->job['frequency'])->toDateTimeString()
        );

        // Set processing false | unlock job
        $this->setProcessing(false);
    }


    protected function createOrder() : array
    {
        $endPoint = "payment/rest/register.do";

        $params = [
            "userName" => Settings::getARCAUsername(),
            "password" => Settings::getARCAPassword(),
            "orderNumber" => str_replace("-", "", Uuid::uuid4()->toString()),
            "amount" => $this->job['amount'],
            "currency" => $this->job['currencyCode'],
            "returnUrl" => "https://kido.am",
            "description" => "Ежемесячный платеж в поддержку Kido.am",
            "language" => "ru",
            "pageView" => "DESKTOP",
            "clientId" => $this->job['clientId']
        ];

        $data = $this->http->get($endPoint . "?" . http_build_query($params));

        $data = $data->getBody()->getContents();

        return json_decode($data, true);
    }

    protected function proceedPayment($orderId) : array
    {
        $endPoint = "payment/rest/paymentOrderBinding.do";

        $data = [
            "userName" => Settings::getARCAUsername(),
            "password" => Settings::getARCAPassword(),
            "bindingId" => $this->job['bindingId'],
            "mdOrder" => $orderId
        ];

        $data = $this->http->post($endPoint, [
            'form_params' => $data
        ]);

        $data = $data->getBody()->getContents();

        return json_decode($data, true);
    }

    protected function setProcessing(bool $processing) : bool
    {
        $pdo = DBConnector::init()->getPDO();

        // Update isProcessing
        $query = $pdo->prepare("update jobs set isProcessing = " . ($processing ? 1 : 0) . " where id = " . $this->job['id']);

        return $query->execute();
    }

    protected function updateNextPaymentDate() : void
    {
        $pdo = DBConnector::init()->getPDO();

        $stmt = $pdo->prepare("update jobs set attempts = ?, nextPaymentDate = ?  where id = ?");
        $stmt->execute([
            0,
            Carbon::parse($this->job['nextPaymentDate'])->addMonths($this->job['frequency'])->toDateTimeString(),
            $this->job['id']
        ]);
    }

    protected function jobFailed($message) : void
    {
        logger("Error creating order | " . $message, "ERROR", $this->job['id']);
        $pdo = DBConnector::init()->getPDO();

        if($this->job['attempts'] >= 2)
        {
            $stmt = $pdo->prepare("update jobs set attempts = ?, nextPaymentDate = ?, status = ?, disableReason = ?, disabledAt = ?  where id = ?");
            $stmt->execute([
                0,
                Carbon::parse($this->job['nextPaymentDate'])->addDay()->toDateTimeString(),
                'DISABLED',
                $message,
                Carbon::now('Europe/Moscow')->toDateTimeString(),
                $this->job['id']
            ]);

            return;
        }

        $stmt = $pdo->prepare("update jobs set attempts = ?, nextPaymentDate = ? where id = ?");
        $stmt->execute([
            ++$this->job['attempts'],
            Carbon::parse($this->job['nextPaymentDate'])->addDay()->toDateTimeString(),
            $this->job['id']
        ]);
    }
}
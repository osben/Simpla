<?php
require_once 'autoload.php';
require_once 'YandexMoneyLogger.php';

if (!date_default_timezone_get()) {
    date_default_timezone_set('Europe/Moscow');
}

use YandexCheckout\Client;
use YandexCheckout\Model\Notification\NotificationSucceeded;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;
use YandexCheckout\Model\NotificationEventType;
use YandexCheckout\Model\PaymentStatus;
use YandexCheckout\Request\Payments\Payment\CreateCaptureRequest;

class YandexMoneyCallbackHandler
{
    public $simplaApi;

    public function __construct($simplaApi)
    {
        $this->simplaApi = $simplaApi;
    }

    public function processReturnUrl()
    {
        $orderId       = $this->simplaApi->request->get('order');
        $order         = $this->simplaApi->orders->get_order(intval($orderId));
        $paymentMethod = $this->simplaApi->payment->get_payment_method(intval($order->payment_method_id));
        $settings      = $this->simplaApi->payment->get_payment_settings($paymentMethod->id);
        $apiClient     = $this->getApiClient($settings['yandex_api_shopid'], $settings['yandex_api_password']);
        $paymentId     = $this->getPaymentId($orderId);
        $logger        = new YandexMoneyLogger($settings['ya_kassa_debug']);
        $apiClient->setLogger($logger);
        try {
            $paymentInfo = $apiClient->getPaymentInfo($paymentId);
            if ($paymentInfo->status == PaymentStatus::WAITING_FOR_CAPTURE) {
                $captureResult = $this->capturePayment($apiClient, $paymentInfo);
                if ($captureResult->status == PaymentStatus::SUCCEEDED) {
                    $this->completePayment($order, $paymentId);
                    $logger->info('Complete payment #'.$paymentId.' orderId: '.$orderId);
                } else {
                    $logger->info('Capture order fail. OrderId: '.$orderId);
                }
            } elseif ($paymentInfo->status == PaymentStatus::CANCELED) {
                $logger->info('Cancel order. OrderId: '.$orderId);
            } elseif ($paymentInfo->status == PaymentStatus::SUCCEEDED) {
                $this->completePayment($order, $paymentId);
                $logger->info('Complete payment #'.$paymentId.' orderId: '.$orderId);
            }

            $return_url = $this->simplaApi->config->root_url.'/order/'.$order->url;
            header('Location: '.$return_url);
            exit;
        } catch (Exception $e) {
            $logger->error($e->getMessage());
            throw $e;
        }
    }

    public function processNotification()
    {
        $body           = @file_get_contents('php://input');
        $callbackParams = json_decode($body, true);
        if (json_last_error()) {
            header("HTTP/1.1 400 Bad Request");
            header("Status: 400 Bad Request");
            exit();
        }

        $notificationModel = ($callbackParams['event'] === NotificationEventType::PAYMENT_SUCCEEDED)
            ? new NotificationSucceeded($callbackParams)
            : new NotificationWaitingForCapture($callbackParams);

        $payment       = $notificationModel->getObject();
        $orderId       = (int)$payment->getMetadata()->offsetGet('order_id');
        $order         = $this->simplaApi->orders->get_order(intval($orderId));
        $paymentMethod = $this->simplaApi->payment->get_payment_method(intval($order->payment_method_id));
        $settings      = $this->simplaApi->payment->get_payment_settings($paymentMethod->id);
        $apiClient     = $this->getApiClient($settings['yandex_api_shopid'], $settings['yandex_api_password']);
        $logger        = new YandexMoneyLogger($settings['ya_kassa_debug']);
        $logger->info('Notification: '.$body);
        $apiClient->setLogger($logger);
        $paymentId = $payment->getId();
        if (!$order) {
            $logger->error('Order not found. OrderId: '.$orderId);
            header("HTTP/1.1 404 Not Found");
            header("Status: 404 Not Found");
            exit();
        }
        $paymentInfo = $apiClient->getPaymentInfo($payment->getId());
        if (!$paymentInfo) {
            $logger->error('Empty payment info. OrderId: '.$orderId);
            header("HTTP/1.1 404 Not Found");
            header("Status: 404 Not Found");
            exit();
        }

        switch ($paymentInfo->status) {
            case PaymentStatus::WAITING_FOR_CAPTURE:
                $captureResult = $this->capturePayment($apiClient, $paymentInfo);
                $logger->info('Capture payment #'.$paymentId.' orderId: '.$orderId);
                if ($captureResult->status === PaymentStatus::SUCCEEDED) {
                    $this->completePayment($order, $paymentId);
                    $logger->info('Complete payment #'.$paymentId.' orderId: '.$orderId);
                } else {
                    $logger->info('Capture order fail. OrderId: '.$orderId);
                }
                header("HTTP/1.1 200 OK");
                header("Status: 200 OK");
                break;
            case PaymentStatus::PENDING:
                $logger->info('Pending payment. OrderId: '.$orderId.' paymentId: '.$paymentId);
                header("HTTP/1.1 400 Bad Request");
                header("Status: 400 Bad Request");
                break;
            case PaymentStatus::SUCCEEDED:
                $this->completePayment($order, $paymentId);
                $logger->info('Complete payment #'.$paymentId.' orderId: '.$orderId);
                header("HTTP/1.1 200 OK");
                header("Status: 200 OK");
                break;
            case PaymentStatus::CANCELED:
                $logger->info('Cancel order. OrderId: '.$orderId);
                header("HTTP/1.1 200 OK");
                header("Status: 200 OK");
                break;
        }
        exit();
    }

    /**
     * @param YandexCheckout\Client $apiClient
     * @param object $payment
     *
     * @return YandexCheckout\Request\Payments\Payment\CreateCaptureResponse
     */
    protected function capturePayment($apiClient, $payment)
    {
        $captureRequest = CreateCaptureRequest::builder()->setAmount($payment->getAmount())->build();

        $result = $apiClient->capturePayment(
            $captureRequest,
            $payment->id
        );

        return $result;
    }

    /**
     * @param int|string $shopId
     * @param string $shopPassword
     *
     * @return Client
     */
    protected function getApiClient($shopId, $shopPassword)
    {
        $apiClient = new Client();
        $apiClient->setAuth($shopId, $shopPassword);

        return $apiClient;
    }

    private function getPaymentId($orderId)
    {
        $sql   = 'SELECT o.payment_details FROM __orders AS o WHERE o.id='.$this->simplaApi->db->escape((int)$orderId);
        $query = $this->simplaApi->db->placehold($sql);
        $this->simplaApi->db->query($query);

        return $this->simplaApi->db->result('payment_details');
    }

    private function completePayment($order, $paymentId)
    {
        $this->simplaApi->orders->pay($order->id);
        $comment = $order->comment." Номер транзакции в Яндекс.Кассе: {$paymentId}. Сумма: {$order->total_price}";
        $this->simplaApi->orders->update_order($order->id, array(
            'paid'    => 1,
            'status'  => 2,
            'comment' => $comment,
        ));

        // отправляем уведомление администратору
        $this->simplaApi->notify->email_order_admin((int)$order->id);
    }
}
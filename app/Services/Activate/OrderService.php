<?php

namespace App\Services\Activate;

use App\Dto\BotDto;
use App\Dto\BotFactory;
use App\Models\Activate\SmsCountry;
use App\Models\Bot\SmsBot;
use App\Models\Order\SmsOrder;
use App\Models\User\SmsUser;
use App\Services\External\BottApi;
use App\Services\External\SmsActivateApi;
use App\Services\MainService;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use RuntimeException;

class OrderService extends MainService
{
    /**
     * @param BotDto $botDto
     * @param string $country_id
     * @param string $services
     * @param array $userData
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createMulti(BotDto $botDto, string $country_id, string $services, array $userData)
    {
        // Создать заказ по апи
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $user = SmsUser::query()->where(['telegram_id' => $userData['user']['telegram_id']])->first();
        if (is_null($user)) {
            throw new RuntimeException('not found user');
        }

        //Создание мультисервиса
        $serviceResults = $smsActivate->getMultiServiceNumber(
            $services,
            $forward = 0,
            $country_id,
        );

        //Получение активных активаций
        $activateActiveOrders = $smsActivate->getActiveActivations();
        $activateActiveOrders = $activateActiveOrders['activeActivations'];

        $orderAmount = 0;
        foreach ($activateActiveOrders as $activateActiveOrder) {
            $orderAmount += $activateActiveOrder['activationCost'];
        }

        //формирование общей цены заказа
        $amountFinal = intval(floatval($orderAmount) * 100);
        $amountFinal = $amountFinal + ($amountFinal * ($botDto->percent / 100));

        //отмена заказа если бабок недостаточно
        if ($amountFinal > $userData['money']) {
            foreach ($serviceResults as $key => $serviceResult) {
                $org_id = intval($serviceResult['activation']);
                $serviceResult = $smsActivate->setStatus($org_id, SmsOrder::ACCESS_CANCEL);
            }
            throw new RuntimeException('Пополните баланс в боте..');
        }

        // Попытаться списать баланс у пользователя
        $result = BottApi::subtractBalance($botDto, $userData, $amountFinal, 'Списание баланса для номера '
            . $serviceResults[0]['phone']);

        // Неудача отмена на сервисе
        if (!$result['result']) {
            foreach ($serviceResults as $key => $serviceResult) {
                $org_id = intval($serviceResult['activation']);
                $serviceResult = $smsActivate->setStatus($org_id, SmsOrder::ACCESS_CANCEL);
            }
            throw new RuntimeException('При списании баланса произошла ошибка: ' . $result['message']);
        }

        // Удача создание заказа в бд
        $country = SmsCountry::query()->where(['org_id' => $country_id])->first();
        $dateTime = intval(time());

        $response = [];

        foreach ($serviceResults as $key => $serviceResult) {
            $org_id = intval($serviceResult['activation']);
            foreach ($activateActiveOrders as $activateActiveOrder) {
                $active_org_id = intval($activateActiveOrder['activationId']);

                if ($org_id == $active_org_id) {
                    //формирование цены для каждого заказа
                    $amountStart = intval(floatval($activateActiveOrder['activationCost']) * 100);
                    $amountFinal = $amountStart + $amountStart * $botDto->percent / 100;

                    $data = [
                        'bot_id' => $botDto->id,
                        'user_id' => $user->id, //
                        'service' => $activateActiveOrder['serviceCode'],
                        'country_id' => $country->id,
                        'org_id' => $activateActiveOrder['activationId'],
                        'phone' => $activateActiveOrder['phoneNumber'],
                        'codes' => null,
                        'status' => SmsOrder::STATUS_WAIT_CODE, //4
                        'start_time' => $dateTime,
                        'end_time' => $dateTime + 1177,
                        'operator' => null,
                        'price_final' => $amountStart,
                        'price_start' => $amountFinal,
                    ];

                    $order = SmsOrder::create($data);
                    $result = $smsActivate->setStatus($order, SmsOrder::ACCESS_RETRY_GET);
                    $result = $this->getStatus($order->org_id, $botDto);

                    array_push($response, [
                        'id' => $order->org_id,
                        'phone' => $order->phone,
                        'time' => $order->start_time,
                        'status' => $order->status,
                        'codes' => null,
                        'country' => $country->org_id,
                        'service' => $order->service,
                        'cost' => $amountFinal
                    ]);
                }
            }

        }

        return $response;
    }

    /**
     * Создание заказа
     *
     * @param array $userData Сущность DTO from bott
     * @param BotDto $botDto
     * @param string $country_id
     * @return array
     * @throws \Exception
     */
    public
    function create(BotDto $botDto, string $country_id, string $service, array $userData): array
    {
        // Создать заказ по апи
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);
        $user = SmsUser::query()->where(['telegram_id' => $userData['user']['telegram_id']])->first();
//        $user = SmsUser::query()->where(['id' => 1])->first();
        if (is_null($user)) {
            throw new RuntimeException('not found user');
        }
//        if (empty($user->service))
//            throw new RuntimeException('Choose service pls');

        $serviceResult = $smsActivate->getNumber(
            $service,
            $country_id
        );

        $org_id = intval($serviceResult[1]);

        $service_price = $smsActivate->getPrices($country_id, $service);
        $service_prices = $service_price[$country_id][$service];
        $price = key($service_prices);

        // Из него получить цену
        $amountStart = (int)ceil(floatval($price) * 100);
        $amountFinal = $amountStart + $amountStart * $botDto->percent / 100;

        if ($amountFinal > $userData['money']) {
            $serviceResult = $smsActivate->setStatus($org_id, SmsOrder::ACCESS_CANCEL);
            throw new RuntimeException('Пополните баланс в боте');
        }
        // Попытаться списать баланс у пользователя
        $result = BottApi::subtractBalance($botDto, $userData, $amountFinal, 'Списание баланса для актвиации номера.');
//
        // Неудача отмена на сервисе
        if (!$result['result']) {
            $serviceResult = $smsActivate->setStatus($org_id, SmsOrder::ACCESS_CANCEL);
            throw new RuntimeException('При списании баланса произошла ошибка: ' . $result['message']);
        }

        // Удача создание заказа в бд
        $country = SmsCountry::query()->where(['org_id' => $country_id])->first();
        $dateTime = intval(time());

        $data = [
            'bot_id' => $botDto->id,
            'user_id' => $user->id,
            'service' => $service,
            'country_id' => $country->id,
            'org_id' => $org_id,
            'phone' => $serviceResult[2],
            'codes' => null,
            'status' => SmsOrder::STATUS_WAIT_CODE, //4
            'start_time' => $dateTime,
            'end_time' => $dateTime + 1177,
            'operator' => null,
            'price_final' => $amountFinal,
            'price_start' => $amountStart,
        ];

        $order = SmsOrder::create($data);
//        $result = $smsActivate->setStatus($order, SmsOrder::ACCESS_RETRY_GET);
//        $result = $this->getStatus($order->org_id, $botDto);

        $result = [
            'id' => $order->org_id,
            'phone' => $serviceResult[2],
            'time' => $dateTime,
            'status' => $order->status,
            'codes' => null,
            'country' => $country->org_id,
            'operator' => null,
            'service' => $service,
            'cost' => $amountFinal
        ];
        return $result;
    }

    /**
     * Отмена заказа со статусом 9
     *
     * @param array $userData
     * @param BotDto $botDto
     * @param SmsOrder $order
     * @return mixed
     */
    public
    function cancel(BotDto $botDto, SmsOrder $order, array $userData)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);
        // Проверить уже отменёный
        if ($order->status == SmsOrder::STATUS_CANCEL)
            throw new RuntimeException('The order has already been canceled');
        if ($order->status == SmsOrder::STATUS_FINISH)
            throw new RuntimeException('The order has not been canceled, the number has been activated, Status 10');
        // Можно отменить только статус 4 и кодов нет
        if (!is_null($order->codes))
            throw new RuntimeException('The order has not been canceled, the number has been activated');

        // Обновить статус setStatus()
        $result = $smsActivate->setStatus($order->org_id, SmsOrder::ACCESS_CANCEL);
        // Проверить статус getStatus()
//        $result = $this->getStatus($order->org_id, $botDto);
//        if ($result != SmsOrder::STATUS_CANCEL)
//            //надо писать лог
//            throw new RuntimeException('При проверке статуса произошла ошибка, вернулся статус: ' . $result);

        $order->status = SmsOrder::STATUS_CANCEL;
        if ($order->save()) {
            // Он же возвращает баланс
            $amountFinal = $order->price_final;
            $result = BottApi::addBalance($botDto, $userData, $amountFinal, 'Возврат баланса, активация отменена');
        } else {
            throw new RuntimeException('Not save order');
        }
        return $result;
    }

    /**
     * Успешное завершение заказа со статусом 10
     *
     * @param BotDto $botDto
     * @param SmsOrder $order
     * @return int
     */
    public
    function confirm(BotDto $botDto, SmsOrder $order)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        if ($order->status == SmsOrder::STATUS_CANCEL)
            throw new RuntimeException('The order has already been canceled');
        if (is_null($order->codes))
            throw new RuntimeException('Попытка установить несуществующий статус');
        if ($order->status == SmsOrder::STATUS_FINISH)
            throw new RuntimeException('The order has not been canceled, the number has been activated, Status 10');

        $result = $smsActivate->setStatus($order->org_id, SmsOrder::ACCESS_ACTIVATION);

        $result = $this->getStatus($order->org_id, $botDto);

        $order->status = SmsOrder::STATUS_FINISH;

        $order->save();

        return SmsOrder::STATUS_FINISH;
    }

    /**
     * Повторное получение СМС
     *
     * @param BotDto $botDto
     * @param SmsOrder $order
     * @return int
     */
    public
    function second(BotDto $botDto, SmsOrder $order)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        if ($order->status == SmsOrder::STATUS_CANCEL)
            throw new RuntimeException('The order has already been canceled');
        if (is_null($order->codes))
            throw new RuntimeException('Попытка установить несуществующий статус');
        if ($order->status == SmsOrder::STATUS_FINISH)
            throw new RuntimeException('The order has not been canceled, the number has been activated, Status 10');

        $result = $smsActivate->setStatus($order->org_id, SmsOrder::ACCESS_READY);

        $result = $this->getStatus($order->org_id, $botDto);

//        if ($result != SmsOrder::STATUS_WAIT_RETRY)
//            throw new RuntimeException('При проверке статуса произошла ошибка, вернулся статус: ' . $result);

        $resultSet = $order->status = SmsOrder::STATUS_WAIT_RETRY;

        $order->save();
        return $resultSet;
    }

    /**
     * Получение активного заказа и обновление кодов
     *
     * @param array $userData
     * @param BotDto $botDto
     * @param SmsOrder $order
     * @return void
     */
    public
    function order(BotDto $botDto, SmsOrder $order, array $userData = null): void
    {
        switch ($order->status) {
            case SmsOrder::STATUS_CANCEL:
            case SmsOrder::STATUS_FINISH:
                break;
            case SmsOrder::STATUS_OK:
            case SmsOrder::STATUS_WAIT_CODE:
            case SmsOrder::STATUS_WAIT_RETRY:
                $resultStatus = $this->getStatus($order->org_id, $botDto);
                switch ($resultStatus) {
                    case SmsOrder::STATUS_FINISH:
                    case SmsOrder::STATUS_CANCEL:
                        break;
                    case SmsOrder::STATUS_OK:
                    case SmsOrder::STATUS_WAIT_CODE:
                    case SmsOrder::STATUS_WAIT_RETRY:
                        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);
                        $orderCode = $smsActivate->getCode($order->org_id);

                        // Есть ли смс
                        $sms = $orderCode;
                        if (is_null($sms))
                            break;
                        $sms = json_encode($sms);
                        if (is_null($order->codes)) {
                            BottApi::createOrder($botDto, $userData, $order->price_final,
                                'Заказ активации для номера ' . $order->phone .
                                ' с смс: ' . $sms . ' (Модуль Sms Hub)');
                        }

                        $order->codes = $sms;
                        $order->status = $resultStatus;
                        $order->save();
                        break;
                    default:
                        throw new RuntimeException('неизвестный статус ' . $resultStatus);
                }
                break;
        }
    }


    /**
     * Крон обновление статусов
     *
     * @return void
     */
    public
    function cronUpdateStatus(): void
    {
        try {
            $statuses = [SmsOrder::STATUS_OK, SmsOrder::STATUS_WAIT_CODE, SmsOrder::STATUS_WAIT_RETRY];

            $orders = SmsOrder::query()->whereIn('status', $statuses)
                ->where('end_time', '<=', time())->get();

            echo "START count:" . count($orders) . PHP_EOL;

            $start_text = "Hub Start count: " . count($orders) . PHP_EOL;
            $this->notifyTelegram($start_text);

            foreach ($orders as $key => $order) {
                echo $order->id . PHP_EOL;
                $bot = SmsBot::query()->where(['id' => $order->bot_id])->first();

                $botDto = BotFactory::fromEntity($bot);
                $result = BottApi::get(
                    $order->user->telegram_id,
                    $botDto->public_key,
                    $botDto->private_key
                );
                echo $order->id . PHP_EOL;


                if (is_null($order->codes)) {
                    echo 'cancel_start' . PHP_EOL;
                    $this->cancel(
                        $botDto,
                        $order,
                        $result['data'],
                    );
                    echo 'cancel_finish' . PHP_EOL;
                } else {
                    echo 'confirm_start' . PHP_EOL;
                    $this->confirm(
                        $botDto,
                        $order
                    );
                    echo 'confirm_finish' . PHP_EOL;
                }
                echo "FINISH" . $order->id . PHP_EOL;
            }

            $finish_text = "Hub finish count: " . count($orders) . PHP_EOL;
            $this->notifyTelegram($finish_text);

        } catch (\Exception $e) {
            $this->notifyTelegram($e->getMessage());
        }
    }

    public function notifyTelegram($text)
    {
        $client = new Client();

        $client->post('https://api.telegram.org/bot6331654488:AAEmDoHZLV6D3YYShrwdanKlWCbo9nBjQy4/sendMessage', [

            RequestOptions::JSON => [
                'chat_id' => 398981226,
                'text' => $text,
            ]
        ]);
    }

    /**
     * Статус заказа с сервиса
     *
     * @param $id
     * @param BotDto $botDto
     * @return mixed
     */
    public
    function getStatus($id, BotDto $botDto)
    {
        $smsActivate = new SmsActivateApi($botDto->api_key, $botDto->resource_link);

        $serviceResult = $smsActivate->getStatus($id);
        return $serviceResult;
    }
}

<?php

namespace App\Services\Activate;

use App\Services\External\SmsActivateApi;
use App\Services\MainService;

class ProductService extends MainService
{
    /**
     * Все доступные сервисы с API
     *
     * @param $country
     * @return array
     */
    public function getAllProducts($country = null)
    {
        //оставить свой API
        $smsActivate = new SmsActivateApi(config('services.key_activate.key'), BotService::DEFAULT_HOST);

        return $smsActivate->getNumbersStatus($country);
    }

    /**
     * Сервисы доступные для конкретной страны
     *
     * @return array
     */
    public function getPricesCountry($bot)
    {
        $smsActivate = new SmsActivateApi($bot->api_key, $bot->resource_link);

        if ($bot->resource_link == BotService::DEFAULT_HOST) {
            $services = $smsActivate->getTopCountriesByService();
            return $this->formingPricesArr($services);
        } else {
            $services = $smsActivate->getPrices();
            return $this->formingPricesArr($services);
        }
    }

    /**
     * @param $services
     * @return array
     */
    private function formingPricesArr($services)
    {
        $result = [];
        foreach ($services as $key => $service) {

            array_push($result, [
                'name' => $key,
                'image' => 'https://smsactivate.s3.eu-central-1.amazonaws.com/assets/ico/' . $key . '0.webp',
            ]);
        }

        return $result;
    }

    /**
     * Формирование списка сервисов с ценой для выбранной страны
     *
     * @param $bot
     * @param $country
     * @return array
     */
    public function getServices($bot, $country = null)
    {
        $smsActivate = new SmsActivateApi($bot->api_key, $bot->resource_link);

        $services = \Cache::get('services_' . $country);
        if($services === null){
            $services = $smsActivate->getPrices($country);
            \Cache::put('services_' . $country, $services, 15);
        }
        $services = current($services);

        $result = [];
        $prices_array = [];

        if (!is_null($bot->black))
            $black_array = explode(',', $bot->black);

        //формирование правильного массива фиксированной цены
        if (!is_null($bot->prices)) {
            $prices = explode(',', $bot->prices);

            foreach ($prices as $price) {
                $price = explode(':', $price);
                $prices_array[$price[0]] = $price[1];
            }
        }

        foreach ($services as $key => $service) {

            if (!is_null($bot->black)) {
                if (in_array($key, $black_array))
                    continue;
            }

            $count = reset($service);

            if (!is_null($bot->prices)) {
                if (array_key_exists($key, $prices_array)) {
                    $pricePercent = $prices_array[$key];
                } else {
                    $price = key($service);
                    $pricePercent = $price + ($price * ($bot->percent / 100));
                }
            } else {
                $price = key($service);
                $pricePercent = $price + ($price * ($bot->percent / 100));
            }

            array_push($result, [
                'name' => $key,
                'image' => 'https://smsactivate.s3.eu-central-1.amazonaws.com/assets/ico/' . $key . '0.webp',
                'count' => $count,
                'cost' => $pricePercent * 100,
            ]);

        }

        return $result;
    }
}

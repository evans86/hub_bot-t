<?php

namespace App\Services\Activate;

use App\Models\Activate\SmsCountry;
use App\Services\External\SmsActivateApi;
use App\Services\MainService;

class CountryService extends MainService
{
    /**
     * Получение, добавление стран и их операторов из API сервиса
     * @return void
     */
    public function getApiCountries()
    {
        //оставить свой API
        $smsActivate = new SmsActivateApi(config('services.key_activate.key'), BotService::DEFAULT_ACTIVATE_HOST);

        $countries = $smsActivate->getCountries();
        dd($countries);

        $this->formingCountriesArr($countries);
    }

    public function getCountries($bot)
    {
        $smsActivate = new SmsActivateApi(config('services.key_activate.key'), BotService::DEFAULT_ACTIVATE_HOST);

        $countries = \Cache::get('countries');
        if($countries === null){
            $countries = $smsActivate->getCountries();
            \Cache::put('countries', $countries, 900);
        }

        $result = [];

        foreach ($countries as $key => $country) {

            array_push($result, [
                'org_id' => $country['id'],
                'name_ru' => $country['rus'],
                'name_en' => $country['eng'],
                'image' => 'https://smsactivate.s3.eu-central-1.amazonaws.com/assets/ico/country/' . $country['id'] . '.svg'
            ]);
        }

        return $result;
    }

    /**
     * Список стран по сервису
     *
     * @param $bot
     * @param $service
     * @return array
     */
    public function getPricesService($bot, $service = null)
    {
        $smsActivate = new SmsActivateApi($bot->api_key, $bot->resource_link);

        $countries = $smsActivate->getPrices(null, $service);

        return $this->formingServicesArr($countries, $bot);
    }

    /**
     * @param $countries
     * @return void
     */
    private function formingCountriesArr($countries)
    {
        foreach ($countries as $key => $country) {

            $data = [
                'org_id' => $country['id'],
                'name_ru' => $country['rus'],
                'name_en' => $country['eng'],
                'image' => 'https://smsactivate.s3.eu-central-1.amazonaws.com/assets/ico/country/' . $country['id'] . '.svg'
            ];

            $country = SmsCountry::updateOrCreate($data);
            $country->save();
        }
    }

    /**
     * Формирование списка стран с ценой для выбранного сервиса
     *
     * @param $countries
     * @param $bot
     * @return array
     */
    private function formingServicesArr($countries, $bot)
    {
        $result = [];
        foreach ($countries as $key => $country) {

            $smsCountry = SmsCountry::query()->where(['org_id' => $key])->first();

            $country = current($country);
            $price = $country["cost"];

            $pricePercent = $price + ($price * ($bot->percent / 100));

            array_push($result, [
                'id' => $smsCountry->org_id,
                'title_ru' => $smsCountry->name_ru,
                'title_eng' => $smsCountry->name_en,
                'image' => $smsCountry->image,
                'count' => $country["count"],
                'cost' => $pricePercent * 100,
            ]);
        }

        return $result;
    }
}

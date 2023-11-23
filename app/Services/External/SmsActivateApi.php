<?php

namespace App\Services\External;

use App\Helpers\BotLogHelpers;
use App\Helpers\OrdersHelper;
use App\Models\Order\SmsOrder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use http\Exception\InvalidArgumentException;
use PHPUnit\TextUI\RuntimeException;

class SmsActivateApi
{
//    private $url = 'https://api.sms-activate.org/stubs/handler_api.php';
    private $url;

    private $apiKey;

    public function __construct($apiKey, $url)
    {
        $this->apiKey = $apiKey;
        $this->url = $url;
    }

    public function getBalance()
    {
        return $this->request(array('api_key' => $this->apiKey, 'action' => __FUNCTION__), 'GET', true, 3);
    }

    public function getBalanceAndCashBack()
    {
        return $this->request(array('api_key' => $this->apiKey, 'action' => __FUNCTION__), 'GET');
    }

    public function getTopCountriesByService($service = '', $freePrice = false)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'service' => $service, '$freePrice' => $freePrice);
        return $this->request($requestParam, 'POST', true);
    }

    public function getNumbersStatus($country = null, $operator = null)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__);
        if ($country) {
            $requestParam['country'] = $country;
        }
        if ($operator && ($country == 0 || $country == 1 || $country == 2)) {
            $requestParam['service'] = $operator;
        }
        $response = array();
        $changeKeys = $this->request($requestParam, 'GET', true);
//        $changeKeys = json_decode($changeKeys, true);
        foreach ($changeKeys as $services => $count) {
            $services = trim($services, "_01");
            $response[$services] = $count;
        }
        unset($changeKeys);
        return $response;
    }

    public function getNumber($service, $country = null, $operator = null)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'service' => $service);
        if ($country) {
            $requestParam['country'] = $country;
        }
        if ($operator && ($country == 0 || $country == 1 || $country == 2)) {
            $requestParam['operator'] = $operator;
        }
        return $this->request($requestParam, 'GET', null, 10); //убрать десятку
    }

    public function getNumberV2($service, $country = null, $forward = 0, $operator = null)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'service' => $service, 'forward' => $forward, 'ref' => 'WEB');
        if (!is_null($country)) {
            $requestParam['country'] = $country;
        }
        if ($operator && ($country == 0 || $country == 1 || $country == 2)) {
            $requestParam['operator'] = $operator;
        }
        return $this->request($requestParam, 'POST', null);
    }

    public function getMultiServiceNumber($services, $forward = 0, $country = null, $operator = null)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'multiService' => $services, 'forward' => $forward, 'ref' => 'WEB');
        if ($country) {
            $requestParam['country'] = $country;
        }
        if ($operator && ($country == 0 || $country == 1 || $country == 2)) {
            $requestParam['operator'] = $operator;
        }
        return $this->request($requestParam, 'POST', true, 3);
    }

    public function setStatus($id, $status, $forward = 0)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'id' => $id, 'status' => $status);

        if ($forward) {
            $requestParam['forward'] = $forward;
        }

        return $this->request($requestParam, 'GET', null, 12);
    }

    public function getStatus($id)
    {
        return $this->request(array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'id' => $id), 'GET', false, 1);
    }

    public function getCode($id)
    {
        return $this->request(array('api_key' => $this->apiKey, 'action' => 'getStatus', 'id' => $id), 'GET', false, 30);
    }

    public function getCountries()
    {
        return $this->request(array('api_key' => $this->apiKey, 'action' => __FUNCTION__), 'GET', true, 11);
    }

    public function getActiveActivations()
    {
        return $this->request(array('api_key' => $this->apiKey, 'action' => __FUNCTION__), 'GET', true);
    }

    public function getAdditionalService($service, $activationId)
    {
        return $this->request(array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'service' => $service, 'id' => $activationId), 'GET', false, 1);
    }

    public function getFullSms($id)
    {
        return $this->request(array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'id' => $id), 'GET');
    }

    public function getPrices($country = null, $service = null)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__);

        if ($country !== null) {
            $requestParam['country'] = $country;
        }
        if ($service) {
            $requestParam['service'] = $service;
        }

        return $this->request($requestParam, 'GET', true);
    }

    public function getRentServicesAndCountries($country = "0", $time = 4, $operator = "any")
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'rent_time' => $time, 'operator' => $operator, 'country' => $country);
        return $this->requestRent($requestParam, 'POST', false, 1);
    }

    public function getOperators($country)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'country' => $country);
        return $this->requestRent($requestParam, 'POST', true);
    }

    public function getPricesActivation($service)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'service' => $service);
        return $this->requestRent($requestParam, 'POST', true);
    }

    public function getPricesVerification($service)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'service' => $service);
        return $this->requestRent($requestParam, 'POST', true);
    }

    public function getRentNumber($service, $country = 0, $time = 4, $url = '', $operator = "any")
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'service' => $service, 'rent_time' => $time, 'operator' => $operator, 'country' => $country, 'url' => $url);
        return $this->requestRent($requestParam, 'POST', true,);
    }

    public function getRentStatus($id)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'id' => $id);
        return $this->requestRent($requestParam, 'POST', true);
    }

    public function setRentStatus($id, $status)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'id' => $id, 'status' => $status);
        return $this->requestRent($requestParam, 'POST', true);
    }

    public function getRentList()
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__);
        return $this->requestRent($requestParam, 'POST', true);
    }

    public function continueRentNumber($id, $time = 4)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'id' => $id, 'rent_time' => $time);
        return $this->requestRent($requestParam, 'POST', true);
    }

    public function getContinueRentPriceNumber($id, $time)
    {
        $requestParam = array('api_key' => $this->apiKey, 'action' => __FUNCTION__, 'id' => $id, 'rent_time' => $time);
        return $this->requestRent($requestParam, 'POST', true, 3);
    }

    /**
     * @param $data
     * @param $method
     * @param null $parseAsJSON
     * @return mixed
     */
    private function request($data, $method, $parseAsJSON = null, $getNumber = null)
    {
        $method = strtoupper($method);

        if (!in_array($method, array('GET', 'POST'))) {
            throw new InvalidArgumentException('Method can only be GET or POST');
        }

        $serializedData = http_build_query($data);

        if ($method === 'GET') {

            if ($getNumber == 11) {
                $result = file_get_contents("$this->url?$serializedData");
                $json_string = stripslashes(html_entity_decode($result));
                $result = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json_string), true);
                return $result;
            }

            //для домена
            $client = new Client(['base_uri' => $this->url]);
            try {
                $response = $client->get('?' . $serializedData);
            } catch (\Throwable $e) {
                BotLogHelpers::notifyBotLog('(🟠E ' . __FUNCTION__ . ' Hub): ' . $e->getMessage());
                \Log::error($e->getMessage());
                throw new RuntimeException('Ошибка соединения с сервером!');
            }

//            ,
//            [
////                        'timeout' => 80, // Response timeout
////                        'connect_timeout' => 80, // Connection timeout
//                'proxy' => 'http://VtZNR9Hb:nXC9nQ45@86.62.52.85:62958/62959',
//
//            ]


            $result = $response->getBody()->getContents();

            if ($getNumber == 12) {
                $parsedResponse = explode(':', $result);
                return OrdersHelper::requestArray($parsedResponse[0]);
            }

            if ($getNumber == 10) {

                $convert_result = explode(':', $result);

                $check = OrdersHelper::requestArray($convert_result[0]);

                if ($check) {
                    throw new RequestError($convert_result[0]);
                }

                return $convert_result;
            }

            //
            if ($getNumber == 20) {
                $convert_result = explode(':', $result);

                $check = OrdersHelper::requestArray($convert_result[0]);

                if ($check) {
                    throw new RequestError(OrdersHelper::requestArray($convert_result[0]));
                }

                return $convert_result;
            }
            //для получения кодов
            if ($getNumber == 30) {
                $convert_result = explode(':', $result, 2);

                if ($convert_result[0] == 'BAD_ACTION')
                    throw new \Exception('Общее неправильное формирование запроса');
                if ($convert_result[0] == 'NO_ACTIVATION')
                    throw new \Exception('Активации не существует.');
                if ($convert_result[0] == 'STATUS_WAIT_CODE')
                    return null;
                if ($convert_result[0] == 'STATUS_WAIT_RETRY')
                    return $convert_result[1];
                if ($convert_result[0] == 'STATUS_OK')
                    return $convert_result[1];

                return $convert_result;
            }

            if ($getNumber == 3) {
                $parsedResponse = explode(':', $result);
                return $parsedResponse[1];
            }

            if ($getNumber == 1) {
                $parsedResponse = explode(':', $result);
                return OrdersHelper::requestArray($parsedResponse[0]);
            }
            $json_string = stripslashes(html_entity_decode($result));
            $result = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json_string), true);
            return $result;
        } else {
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => $serializedData
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents($this->url, false, $context);
            if ($getNumber == 1) {
                return OrdersHelper::requestArray($result);
            }

            if (OrdersHelper::requestArray($result) == false) {
                $parsedResult = json_decode($result, true);
                return $parsedResult;
            } else {
                throw new \RuntimeException(OrdersHelper::requestArray($result));
//                return OrdersHelper::requestArray($result);
            }


//            return $result;
        }

//        $responseError = new SmsActivateErrors($result);
//        $check = $responseError->checkExist($result);
//
//        try {
//            if ($check) {
//                throw new \Exception($result);
//            }
//        } catch (\Exception $e) {
//            return $e->getResponseCode();
//        }
//
//        if ($parseAsJSON) {
//            return json_decode($result, true);
//        }

//        $parsedResponse = explode(':', $result);

//        if ($getNumber == 1) {
//            return array('id' => $parsedResponse[1], 'number' => $parsedResponse[2]);
//        }
//        if ($getNumber == 2) {
//            return array('status' => $parsedResponse[0], 'code' => $parsedResponse[1]);
//        }
//        if ($getNumber == 3) {
//            return array('status' => $parsedResponse[0]);
//        }
//        return $parsedResponse[1];
    }

    private
    function requestRent($data, $method, $parseAsJSON = null, $getNumber = null)
    {
        $method = strtoupper($method);

        if (!in_array($method, array('GET', 'POST'))) {
            throw new InvalidArgumentException('Method can only be GET or POST');
        }
        $serializedData = http_build_query($data);

        if ($method === 'GET') {
            $request_url = "$this->url?$serializedData";
            $result = file_get_contents($request_url);
        } else {
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => $serializedData
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents($this->url, false, $context);
        }
        if ($getNumber == 1) {
            $result = json_decode($result, true);
            return $result;
        }

        if ($parseAsJSON) {
            $result = json_decode($result, true);
//            dd($result);
            if (isset($result['message'])) {
                $responsError = new ErrorCodes($result['message']);
                $check = $responsError->checkExist($result['message']);  // раскоментить если необходимо включить исключения для Аренды
                if ($check) {
                    throw new RequestError($result['message']);
                }
            }
//            if ($getNumber == 3){
//                return $result['price'];
//            }else{
            return $result;

        }
        return $result;
    }
}



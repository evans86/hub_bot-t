<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiHelpers;
use App\Helpers\BotLogHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\api\ProductResource;
use App\Models\Bot\SmsBot;
use App\Services\Activate\ProductService;
use App\Services\Activate\UserService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * @var ProductService
     */
    private ProductService $productService;
    /**
     * @var UserService
     */
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->productService = new ProductService();
    }

    /**
     * Передача значений доступных сервисов
     *
     * @param Request $request
     * @return array|string
     */
    public function index(Request $request)
    {
        try {
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            $bot = SmsBot::query()->where('public_key', $request->public_key)->first();
            $products = $this->productService->getPricesCountry($bot);
            return ApiHelpers::success($products);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🟠R '.__FUNCTION__.' Hub): ' . $r->getMessage());
            return ApiHelpers::error('Ошибка получения данных провайдера');
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🟠E '.__FUNCTION__.' Hub): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Products error');
        }
    }

    /**
     * Задать значение сервиса
     *
     * @param Request $request
     * @return array|string
     */
    public function setService(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            if (is_null($request->service))
                return ApiHelpers::error('Not found params: service');
            if (is_null($request->user_secret_key))
                return ApiHelpers::error('Not found params: user_secret_key');
            $user = $this->userService->updateService($request->user_id, $request->service);
            return ApiHelpers::success(ProductResource::generateUserArray($user));
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🟠R '.__FUNCTION__.' Hub): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🟠E '.__FUNCTION__.' Hub): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Set service error');
        }
    }

    /**
     * @param Request $request
     * @return array|string
     */
    public function getServices(Request $request)
    {
        try {
            if (is_null($request->country))
                return ApiHelpers::error('Not found params: country');
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            $bot = SmsBot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $countries = $this->productService->getServices($bot, $request->country);
            return ApiHelpers::success($countries);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🟠R '.__FUNCTION__.' Hub): ' . $r->getMessage());
            return ApiHelpers::error('Ошибка получения данных провайдера');
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🟠E '.__FUNCTION__.' Hub): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Get service error');
        }
    }
}

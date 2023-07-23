<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiHelpers;
use App\Helpers\BotLogHelpers;
use App\Http\Controllers\Controller;
use App\Models\Bot\SmsBot;
use App\Models\User\SmsUser;
use App\Services\Activate\CountryService;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * @var CountryService
     */
    private CountryService $countryService;

    public function __construct()
    {
        $this->countryService = new CountryService();
    }

    /**
     * Передача списка стран согласно коллекции
     *
     * Request[
     *  'user_id'
     *  'public_key'
     * ]
     *
     * @param Request $request
     * @return array|string
     */
    public function index(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            $user = SmsUser::query()->where(['telegram_id' => $request->user_id])->first();
            if (is_null($user))
                return ApiHelpers::error('Not found: user');
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            $bot = SmsBot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $countries = $this->countryService->getPricesService($bot, $user->service);
            return ApiHelpers::success($countries);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🟠R '.__FUNCTION__.' Hub): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🟠E '.__FUNCTION__.' Hub): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Countries error');
        }
    }

    /**
     * Формирование списка стран для мультисервиса
     *
     * @param Request $request
     * @return array|string
     */
    public function getCountries(Request $request)
    {
        try {
            if (is_null($request->public_key))
                return ApiHelpers::error('Not found params: public_key');
            $bot = SmsBot::query()->where('public_key', $request->public_key)->first();
            if (empty($bot))
                return ApiHelpers::error('Not found module.');

            $countries = $this->countryService->getCountries($bot);
            return ApiHelpers::success($countries);
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🟠R '.__FUNCTION__.' Hub): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🟠E '.__FUNCTION__.' Hub): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Multi-services countries error');
        }
    }
}

<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiHelpers;
use App\Helpers\BotLogHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\api\UserResource;
use App\Services\Activate\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @var UserService
     */
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Получение значений пользователя
     *
     * Request[
     *  'user_id'
     * ]
     *
     * @param Request $request
     * @return array|string
     */
    public function getUser(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            $user = $this->userService->getOrCreate($request->user_id);
            return ApiHelpers::success(UserResource::generateUserArray($user));
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🟠R '.__FUNCTION__.' Hub): ' . $r->getMessage());
            return ApiHelpers::error('Ошибка получения данных провайдера');
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🟠E '.__FUNCTION__.' Hub): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Get user error');
        }
    }

    /**
     * Установить значение языка для пользователя
     *
     * Request[
     *  'user_id'
     *  'language'
     *  'user_secret_key'
     * ]
     *
     * @param Request $request
     * @return array|string
     */
    public function setLanguage(Request $request)
    {
        try {
            if (is_null($request->user_id))
                return ApiHelpers::error('Not found params: user_id');
            if (is_null($request->language))
                return ApiHelpers::error('Not found params: language');
            if (is_null($request->user_secret_key))
                return ApiHelpers::error('Not found params: user_secret_key');
            $user = $this->userService->updateLanguage($request->user_id, $request->language);
            return ApiHelpers::success(UserResource::generateUserArray($user));
        } catch (\RuntimeException $r) {
            BotLogHelpers::notifyBotLog('(🟠R '.__FUNCTION__.' Hub): ' . $r->getMessage());
            return ApiHelpers::error($r->getMessage());
        } catch (\Exception $e) {
            BotLogHelpers::notifyBotLog('(🟠E '.__FUNCTION__.' Hub): ' . $e->getMessage());
            \Log::error($e->getMessage());
            return ApiHelpers::error('Language error');
        }
    }
}

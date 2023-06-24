<?php

namespace App\Helpers;

class OrdersHelper
{
    /**
     * @param $result
     * @return false|mixed
     */
    public static function requestArray($result)
    {
        $errorCodes = [
            'STATUS_OK' => 7,
            'ACCESS_ACTIVATION' => 6, //Сервис успешно активирован
            'ACCESS_CANCEL' => 8, //Активация отменена
            'ACCESS_READY' => 3, //Ожидание нового смс
            'ACCESS_RETRY_GET' => 1, //Готовность номера подтверждена
            'ACCOUNT_INACTIVE' => 'Свободных номеров нет',
            'ALREADY_FINISH' => 'Аренда уже завершена',
            'ALREADY_CANCEL' => 'Аренда уже отменена',
            'BAD_ACTION' => 'Некорректное действие (параметр action)',
            'BAD_SERVICE' => 'Некорректное наименование сервиса',
            'BAD_KEY' => 'Неверный API ключ доступа',
            'BAD_STATUS' => 'Попытка установить несуществующий статус',
            'BANNED' => 'Нет соединения с серверами провайдера (блокировка)',
            'CANT_CANCEL' => 'Невозможно отменить аренду (прошло более 20 мин.)',
            'ERROR_SQL' => 'Один из параметров имеет недопустимое значение',
            'NO_NUMBERS' => 'Номера закончились, используйте другую страну',
            'NO_BALANCE' => 'Создатель бота должен пополнить баланс в сервисе',
            'NO_YULA_MAIL' => 'Необходимо иметь на счету более 500 рублей для покупки сервисов холдинга Mail.ru и Mamba',
            'NO_CONNECTION' => 'Нет соединения с серверами провайдера',
            'NO_ID_RENT' => 'Не указан id аренды',
            'NO_ACTIVATION' => 'Указанного id активации не существует',
            'STATUS_CANCEL' => 9, //'Активация/аренда отменена',
            'STATUS_FINISH' => 10,//'Аренда оплачена и завершена',
            'STATUS_WAIT_CODE' => 4, //Ожидание первой смс
            'STATUS_WAIT_RETRY' => 5, //Ожидание уточнения кода,
            'STATUS_WAIT_RESEND' => 19, //ожидание повторной отправки смс,
            'SQL_ERROR' => 'Один из параметров имеет недопустимое значение',
            'INVALID_PHONE' => 'Номер арендован не вами (неправильный id аренды)',
            'INCORECT_STATUS' => 'Отсутствует или неправильно указан статус',
            'WRONG_SERVICE' => 'Сервис не поддерживает переадресацию',
            'WHATSAPP_NOT_AVAILABLE' => 'Сервис WhatsApp недоступен для выбранной страны',
            'WRONG_SECURITY' => 'Ошибка при попытке передать ID активации без переадресации, или же завершенной/не активной активации',
        ];

        if (array_key_exists($result, $errorCodes)) {
            return $errorCodes[$result];
        } else {
            return false;
        }
    }

    public static function statusList(): array
    {
        return [
            1 => 'Готовность номера (Состояние)',
            2 => 'Нет свободных номеров',
            3 => 'Ожидание нового смс (Состояние)',
            4 => 'Ожидание смс',
            5 => 'Ожидание уточнения',
            6 => 'Сервис активирован (Состояние)',
            7 => 'OK',
            8 => 'Активация отменена (Состояние)',
            9 => 'Активация отменена',
            10 => 'Активация успешно активирована',
        ];
    }

    public static function statusLabel($status): string
    {
        switch ($status) {
            case 1:
            case 2:
            case 3:
            case 7:
            case 6:
            case 8:
                $class = 'badge bg-info';
                break;
            case 4:
            case 5:
                $class = 'badge bg-warning';
                break;
            case 10:
                $class = 'badge bg-success';
                break;
            case 9:
                $class = 'badge bg-danger';
                break;
            default:
                $class = 'badge bg-default';
        }


        return '<span class="' . $class . '">' . \Arr::get(self::statusList(), $status) . '</span>';
    }
}

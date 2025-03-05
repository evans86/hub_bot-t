<?php

namespace App\Http\Controllers\Activate;

use App\Models\Bot\SmsBot;

class BotController
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $bots = SmsBot::orderBy('id', 'DESC')->Paginate(10);

        //даты последней выплаты минус бот созданный для примера
        $newBots = count(SmsBot::query()->where('created_at', '>', '2025-02-10 20:21:56')->get());
        $allCount = count(SmsBot::get());

        return view('activate.bot.index', compact(
            'bots',
            'newBots',
            'allCount'
        ));
    }
}

<?php

namespace App\Http\Controllers\Activate;

use App\Models\Order\SmsOrder;
use Carbon\Carbon;

class OrderController
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $orders = SmsOrder::orderBy('id', 'DESC')->limit(1000)->Paginate(15);

        $allCount = SmsOrder::count();
        $successCount = SmsOrder::query()->where('status', SmsOrder::STATUS_FINISH)->count();
        $cancelCount = SmsOrder::query()->where('status', SmsOrder::STATUS_CANCEL)->count();

        $todayOrders = SmsOrder::whereDate('created_at', Carbon::today())->count();
        $todaySuccess = SmsOrder::query()->whereDate('created_at', Carbon::today())->
        where('status', SmsOrder::STATUS_FINISH)->count();
        $todayCancel = SmsOrder::query()->whereDate('created_at', Carbon::today())->
        where('status', SmsOrder::STATUS_CANCEL)->count();

        return view('activate.order.index', compact(
            'orders',
            'allCount',
            'successCount',
            'cancelCount',
            'todayOrders',
            'todaySuccess',
            'todayCancel',
        ));
    }
}

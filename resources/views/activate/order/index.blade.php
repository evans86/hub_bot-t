{{--@extends('layouts.main')--}}
@extends('layouts.app', ['page' => __('Заказы'), 'pageSlug' => 'orders'])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card ">
                <div class="card-header">
                    <h4 class="card-title"> Заказы</h4>
                </div>
{{--                <div class="card-body">--}}
{{--                    <div class="table-responsive">--}}
{{--                        <table class="table tablesorter " id="">--}}
{{--                            <thead class=" text-primary">--}}
{{--                            <tr>--}}
{{--                                <th class="text-center">Общее число активаций (сегодня)</th>--}}
{{--                                <th class="text-center">Успешно завершенные (сегодня)</th>--}}
{{--                                <th class="text-center">Отмененые активации (сегодня)</th>--}}
{{--                            </tr>--}}
{{--                            </thead>--}}
{{--                            <tbody>--}}
{{--                            <tr>--}}
{{--                                <th class="text-center">{{ $allCount }} ({{ $todayOrders }})</th>--}}
{{--                                <th class="text-center">{{ $successCount }} ({{ $todaySuccess }})</th>--}}
{{--                                <th class="text-center">{{ $cancelCount }} ({{ $todayCancel }})</th>--}}
{{--                            </tr>--}}
{{--                            </tbody>--}}
{{--                        </table>--}}
{{--                    </div>--}}
{{--                </div>--}}
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table tablesorter " id="">
                            <thead class=" text-primary">
                            <tr>
                                <th class="text-center">ID</th>
                                <th class="text-center">Сервис ID</th>
                                <th class="text-center">Пользователь</th>
                                <th class="text-center">Номер телефона</th>
                                <th class="text-center">Страна</th>
                                <th class="text-center">Оператор</th>
                                <th class="text-center">Сервис</th>
                                <th class="text-center">Статус</th>
                                <th class="text-center">Коды</th>
                                <th class="text-center">Бот</th>
                                <th class="text-center">Создан в сервисе</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td class="text-center">{{ $order->id }}</td>
                                    <td class="text-center">{{ $order->org_id }}</td>
                                    <td class="text-center">{{ $order->user->telegram_id }}</td>
                                    <td class="text-center">{{ $order->phone }}</td>
                                    <td class="text-center">{{ $order->country->name_en }}<img
                                            src={{ $order->country->image }} width="24"></td>
                                    <td class="text-center">{{ $order->operator }}</td>
                                    <td class="text-center"><img class="service_img"
                                                                 src="https://smsactivate.s3.eu-central-1.amazonaws.com/assets/ico/{{ $order->service }}0.webp"
                                                                 width="24"></td>
                                    <td class="text-center">{!!\App\Helpers\OrdersHelper::statusLabel($order->status)!!}</td>
                                    <td class="text-center"><code>{{ $order->codes }}</code></td>
                                    <td class="text-center">{{ $order->bot_id }}</td>
                                    <td class="text-center">{{\Carbon\Carbon::createFromTimestamp($order->start_time)->toDateTimeString()}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex">
                        {!! $orders->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

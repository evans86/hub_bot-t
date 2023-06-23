<div class="sidebar">
    <div class="sidebar-wrapper">
        <div class="logo">
            <a href="/" class="simple-text logo-mini">{{ __('HB') }}</a>
            <a href="/" class="simple-text logo-normal">{{ __('HUB') }}</a>
        </div>
        <ul class="nav">
            <li @if ($pageSlug == 'countries') class="active " @endif>
                <a href="{{ route('activate.countries.index') }}">
                    <i class="tim-icons icon-world"></i>
                    <p>{{ __('Список стран') }}</p>
                </a>
            </li>
{{--            <li @if ($pageSlug == 'products') class="active " @endif>--}}
{{--                <a href="{{ route('activate.product.index') }}">--}}
{{--                    <i class="tim-icons icon-notes"></i>--}}
{{--                    <p>{{ __('Список сервисов') }}</p>--}}
{{--                </a>--}}
{{--            </li>--}}
            <li @if ($pageSlug == 'users') class="active " @endif>
                <a href="{{ route('users.index') }}">
                    <i class="tim-icons icon-single-02"></i>
                    <p>{{ __('Пользователи') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'orders') class="active " @endif>
                <a href="{{ route('activate.order.index') }}">
                    <i class="tim-icons icon-send"></i>
                    <p>{{ __('Заказы') }}</p>
                </a>
            </li>
{{--            <li @if ($pageSlug == 'rents') class="active " @endif>--}}
{{--                <a href="{{ route('activate.rent.index') }}">--}}
{{--                    <i class="tim-icons icon-spaceship"></i>--}}
{{--                    <p>{{ __('Аренды') }}</p>--}}
{{--                </a>--}}
{{--            </li>--}}
            <li @if ($pageSlug == 'bots') class="active " @endif>
                <a href="{{ route('activate.bot.index') }}">
                    <i class="tim-icons icon-controller"></i>
                    <p>{{ __('Боты') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'icons') class="active " @endif>
                <a href="{{ route('pages.icons') }}">
                    <i class="tim-icons icon-atom"></i>
                    <p>{{ __('Icons') }}</p>
                </a>
            </li>
{{--            <li @if ($pageSlug == 'notifications') class="active " @endif>--}}
{{--                <a href="{{ route('pages.notifications') }}">--}}
{{--                    <i class="tim-icons icon-bell-55"></i>--}}
{{--                    <p>{{ __('Notifications') }}</p>--}}
{{--                </a>--}}
{{--            </li>--}}
        </ul>
    </div>
</div>

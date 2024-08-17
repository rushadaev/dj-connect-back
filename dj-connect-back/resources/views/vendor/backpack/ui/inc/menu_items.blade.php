{{-- Этот файл используется для пунктов меню любой темы Backpack v6 --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<x-backpack::menu-item title="Выплаты" icon="la la-money" :link="backpack_url('payout')" />
<x-backpack::menu-item title="Заказы" icon="la la-shopping-cart" :link="backpack_url('order')" />
<x-backpack::menu-item title="DJ" icon="la la-headphones" :link="backpack_url('d-j')" />
<x-backpack::menu-item title="Треки" icon="la la-music" :link="backpack_url('track')" />
<x-backpack::menu-item title="Транзакции" icon="la la-exchange" :link="backpack_url('transaction')" />
<x-backpack::menu-item title="Пользователи" icon="la la-users" :link="backpack_url('user')" />
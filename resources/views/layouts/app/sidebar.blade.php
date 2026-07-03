<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Menu" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    @can('manage users')
                    <flux:sidebar.item icon="user-circle" :href="route('users.index')" :current="request()->routeIs('users.*')">User</flux:sidebar.item>
                    @endcan
                    @can('manage roles and permissions')
                    <flux:sidebar.item icon="shield-check" :href="route('roles.index')" :current="request()->routeIs('roles.*')">Role</flux:sidebar.item>
                    @endcan
                    @can('manage members')
                    <flux:sidebar.item icon="users" :href="route('members.index')" :current="request()->routeIs('members.*')">Member</flux:sidebar.item>
                    @endcan
                    @can('manage payments')
                    <flux:sidebar.item icon="credit-card" :href="route('transactions.index')" :current="request()->routeIs('transactions.*')">Transaksi</flux:sidebar.item>
                    @endcan
                    @can('manage packages')
                    <flux:sidebar.item icon="archive-box" :href="route('packages.index')" :current="request()->routeIs('packages.*')">Paket</flux:sidebar.item>
                    @endcan
                    @can('manage trainers')
                    <flux:sidebar.item icon="user-group" :href="route('personal-trainers.index')" :current="request()->routeIs('personal-trainers.*')">Trainer</flux:sidebar.item>
                    @endcan
                    @can('view reports')
                    <flux:sidebar.item icon="chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.index')">Laporan</flux:sidebar.item>
                    @endcan
                    @can('view own membership')
                    <flux:sidebar.item icon="identification" :href="route('membership.show')" :current="request()->routeIs('membership.show')">Membership</flux:sidebar.item>
                    @endcan
                    @can('view own workout program')
                    <flux:sidebar.item icon="bolt" :href="route('my-program.index')" :current="request()->routeIs('my-program.index')">Program</flux:sidebar.item>
                    @endcan
                    @can('view own payments')
                    <flux:sidebar.item icon="credit-card" :href="route('payments.index')" :current="request()->routeIs('payments.index')">Pembayaran</flux:sidebar.item>
                    @endcan
                    @can('view workout catalog')
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('workout-programs.index')" :current="request()->routeIs('workout-programs.*')">Program</flux:sidebar.item>
                    @endcan
                    @can('view assigned members')
                    <flux:sidebar.item icon="users" :href="route('trainer-members.index')" :current="request()->routeIs('trainer-members.*')">Member Binaan</flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>


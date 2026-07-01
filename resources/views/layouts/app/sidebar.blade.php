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
                    @role('admin')
                    <flux:sidebar.item icon="users" :href="route('admin.members')" :current="request()->routeIs('admin.members*')">Member</flux:sidebar.item>
                    <flux:sidebar.item icon="credit-card" :href="route('admin.transactions')" :current="request()->routeIs('admin.transactions*')">Transaksi</flux:sidebar.item>
                    <flux:sidebar.item icon="archive-box" :href="route('admin.packages')" :current="request()->routeIs('admin.packages*')">Paket</flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('admin.trainers')" :current="request()->routeIs('admin.trainers*')">Trainer</flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('admin.reports')" :current="request()->routeIs('admin.reports')">Laporan</flux:sidebar.item>
                    @endrole
                    @role('member')
                    <flux:sidebar.item icon="identification" :href="route('member.membership')" :current="request()->routeIs('member.membership')">Membership</flux:sidebar.item>
                    <flux:sidebar.item icon="bolt" :href="route('member.programs')" :current="request()->routeIs('member.programs')">Program</flux:sidebar.item>
                    <flux:sidebar.item icon="credit-card" :href="route('member.payments')" :current="request()->routeIs('member.payments')">Pembayaran</flux:sidebar.item>
                    @endrole
                    @role('personal_trainer')
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('trainer.programs')" :current="request()->routeIs('trainer.programs*')">Program</flux:sidebar.item>
                    <flux:sidebar.item icon="bolt" :href="route('trainer.exercises')" :current="request()->routeIs('trainer.exercises*')">Latihan</flux:sidebar.item>
                    @endrole
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

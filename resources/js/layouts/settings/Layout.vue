<script setup lang="ts">
import { toUrl, urlIsActive } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profil',
        href: editProfile(),
    },
    {
        title: 'Heslo',
        href: editPassword(),
    },
    {
        title: 'Dvojfaktorová ochrana',
        href: show(),
    },
    {
        title: 'Vzhľad',
        href: editAppearance(),
    },
];

const currentPath = typeof window !== undefined ? window.location.pathname : '';
</script>

<template>
    <div class="relative min-h-[calc(100vh-4rem)] bg-[#f7f4ee] text-slate-900">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(251,191,36,0.16),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(56,189,248,0.12),transparent_60%)]"></div>

        <div class="mx-auto max-w-5xl px-6 py-8">
            <div class="flex flex-col gap-2">
                <h1 class="text-3xl font-display">Nastavenia</h1>
                <p class="text-slate-600">Správa profilu a nastavení účtu</p>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[220px_1fr]">
                <aside class="rounded-3xl bg-white/80 ring-1 ring-black/5 p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Sekcie</div>
                    <nav class="mt-3 flex flex-col gap-2">
                        <Link
                            v-for="item in sidebarNavItems"
                            :key="toUrl(item.href)"
                            :href="item.href"
                            :class="[
                                'rounded-xl px-3 py-2 text-sm font-medium transition',
                                urlIsActive(item.href, currentPath)
                                    ? 'bg-slate-900 text-white'
                                    : 'text-slate-700 hover:bg-slate-100',
                            ]"
                        >
                            {{ item.title }}
                        </Link>
                    </nav>
                </aside>

                <div class="rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
                    <section class="space-y-10">
                        <slot />
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>

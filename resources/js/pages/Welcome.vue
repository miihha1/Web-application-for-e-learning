<script setup lang="ts">
import { useLanguage } from '@/composables/useLanguage';
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

withDefaults(
    defineProps<{
        canRegister?: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const { locale, setLocale } = useLanguage();

const translations = {
    sk: {
        metaTitle: 'E-learning',
        appTitle: 'Webová aplikácia pre e-learning',
        subtitle: 'Bakalárska práca • pracovný prototyp',
        dashboard: 'Prehľad',
        login: 'Prihlásenie',
        register: 'Registrácia',
        eyebrow: 'Kurzy • lekcie • testy',
        title: 'Študuj online bez zbytočného šumu.',
        description:
            'Zrozumiteľná platforma pre kurzy, lekcie a záverečné testy. Intuitívne rozhranie, prehľadný progres a rýchly štart.',
        start: 'Začať teraz',
        signIn: 'Prihlásiť sa',
        goDashboard: 'Prejsť do prehľadu',
        viewCourses: 'Zobraziť kurzy',
        modulesLabel: 'Moduly',
        modulesValue: 'Kurzy a lekcie',
        progressLabel: 'Progres',
        progressValue: 'Jasný prehľad',
        testsLabel: 'Testy',
        testsValue: 'Rýchla kontrola',
        howItWorks: 'Ako to funguje',
        step1: 'Vyberte si kurz a prejdite lekcie v správnom poradí.',
        step2: 'Sledujte progres a vracajte sa k náročným témam.',
        step3: 'Absolvujte test a ihneď získate výsledok aj históriu.',
        studentsLabel: 'Pre študentov',
        studentsTitle: 'Jednoduchá navigácia',
        studentsText: 'Bez zbytočností. Len to potrebné.',
        teachersLabel: 'Pre učiteľov',
        teachersTitle: 'Štruktúra kurzu',
        teachersText: 'Pohodlná správa lekcií a testov.',
        tipLabel: 'Tip',
        tipText: 'Po prihlásení je k dispozícii prehľad s rýchlym zhrnutím.',
        language: 'Jazyk',
    },
    en: {
        metaTitle: 'E-learning',
        appTitle: 'Web application for e-learning',
        subtitle: 'Bachelor thesis • working prototype',
        dashboard: 'Dashboard',
        login: 'Log in',
        register: 'Register',
        eyebrow: 'Courses • lessons • tests',
        title: 'Study online without unnecessary noise.',
        description:
            'A clear platform for courses, lessons, and final tests. Intuitive interface, visible progress, and a fast start.',
        start: 'Start now',
        signIn: 'Log in',
        goDashboard: 'Go to dashboard',
        viewCourses: 'View courses',
        modulesLabel: 'Modules',
        modulesValue: 'Courses and lessons',
        progressLabel: 'Progress',
        progressValue: 'Clear overview',
        testsLabel: 'Tests',
        testsValue: 'Quick check',
        howItWorks: 'How it works',
        step1: 'Choose a course and complete the lessons in the correct order.',
        step2: 'Track your progress and return to topics that need more practice.',
        step3: 'Complete the test and immediately see your result and history.',
        studentsLabel: 'For students',
        studentsTitle: 'Simple navigation',
        studentsText: 'No clutter. Only what you need.',
        teachersLabel: 'For teachers',
        teachersTitle: 'Course structure',
        teachersText: 'Convenient management of lessons and tests.',
        tipLabel: 'Tip',
        tipText: 'After logging in, the dashboard provides a quick summary.',
        language: 'Language',
    },
} as const;

const t = computed(() => translations[locale.value]);
</script>

<template>
    <Head :title="t.metaTitle" />

    <div class="min-h-screen bg-[#f6f2ea] text-slate-900" data-i18n-managed>
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(60%_80%_at_20%_10%,rgba(253,230,138,0.55),transparent_60%),radial-gradient(60%_80%_at_90%_20%,rgba(251,146,60,0.25),transparent_65%),radial-gradient(80%_80%_at_60%_90%,rgba(94,234,212,0.25),transparent_60%)]"></div>
        <div class="absolute inset-x-0 top-0 -z-10 h-40 bg-gradient-to-b from-white/70 to-transparent"></div>

        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-6">
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-2xl bg-white shadow-[0_10px_30px_-15px_rgba(0,0,0,0.4)] ring-1 ring-black/5">
                    <span class="font-display text-lg text-slate-900">EL</span>
                </div>
                <div>
                    <div class="font-semibold leading-5">{{ t.appTitle }}</div>
                    <div class="text-xs leading-4 text-slate-500">{{ t.subtitle }}</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden items-center gap-1 rounded-xl bg-white/70 p-1 text-xs ring-1 ring-black/5 sm:flex" :aria-label="t.language">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 transition"
                        :class="locale === 'sk' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                        @click="setLocale('sk')"
                    >
                        SK
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 transition"
                        :class="locale === 'en' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                        @click="setLocale('en')"
                    >
                        EN
                    </button>
                </div>

                <Link
                    v-if="$page.props.auth?.user"
                    :href="dashboard()"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white transition hover:bg-slate-800"
                >
                    {{ t.dashboard }}
                </Link>

                <template v-else>
                    <Link
                        :href="login()"
                        class="rounded-lg px-4 py-2 text-sm ring-1 ring-slate-300 transition hover:ring-slate-400"
                    >
                        {{ t.login }}
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="rounded-lg bg-amber-500 px-4 py-2 text-sm text-slate-900 transition hover:bg-amber-400"
                    >
                        {{ t.register }}
                    </Link>
                </template>
            </div>
        </div>

        <div class="mx-auto flex max-w-6xl justify-end px-6 sm:hidden">
            <div class="flex items-center gap-1 rounded-xl bg-white/70 p-1 text-xs ring-1 ring-black/5" :aria-label="t.language">
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 transition"
                    :class="locale === 'sk' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                    @click="setLocale('sk')"
                >
                    SK
                </button>
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 transition"
                    :class="locale === 'en' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                    @click="setLocale('en')"
                >
                    EN
                </button>
            </div>
        </div>

        <div class="mx-auto max-w-6xl px-6 py-10">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs text-slate-700 ring-1 ring-black/5">
                        {{ t.eyebrow }}
                    </div>

                    <h1 class="mt-5 font-display text-4xl leading-tight sm:text-5xl">
                        {{ t.title }}
                    </h1>

                    <p class="mt-4 max-w-xl text-slate-700">
                        {{ t.description }}
                    </p>

                    <div class="mt-7 flex flex-wrap gap-3">
                        <Link
                            v-if="!$page.props.auth?.user"
                            :href="register()"
                            class="rounded-xl bg-slate-900 px-5 py-3 font-medium text-white transition hover:bg-slate-800"
                        >
                            {{ t.start }}
                        </Link>

                        <Link
                            v-if="!$page.props.auth?.user"
                            :href="login()"
                            class="rounded-xl px-5 py-3 font-medium ring-1 ring-slate-300 transition hover:ring-slate-400"
                        >
                            {{ t.signIn }}
                        </Link>

                        <Link
                            v-else
                            :href="dashboard()"
                            class="rounded-xl bg-slate-900 px-5 py-3 font-medium text-white transition hover:bg-slate-800"
                        >
                            {{ t.goDashboard }}
                        </Link>

                        <Link
                            :href="'/courses'"
                            class="rounded-xl bg-white/80 px-5 py-3 font-medium ring-1 ring-black/5 transition hover:bg-white"
                        >
                            {{ t.viewCourses }}
                        </Link>
                    </div>

                    <div class="mt-8 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                        <div class="rounded-2xl bg-white/70 p-4 ring-1 ring-black/5">
                            <div class="text-xs text-slate-500">{{ t.modulesLabel }}</div>
                            <div class="mt-1 font-semibold">{{ t.modulesValue }}</div>
                        </div>
                        <div class="rounded-2xl bg-white/70 p-4 ring-1 ring-black/5">
                            <div class="text-xs text-slate-500">{{ t.progressLabel }}</div>
                            <div class="mt-1 font-semibold">{{ t.progressValue }}</div>
                        </div>
                        <div class="rounded-2xl bg-white/70 p-4 ring-1 ring-black/5">
                            <div class="text-xs text-slate-500">{{ t.testsLabel }}</div>
                            <div class="mt-1 font-semibold">{{ t.testsValue }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid animate-in gap-4 fade-in slide-in-from-bottom-6 delay-150 duration-700">
                    <div class="rounded-3xl bg-white/80 p-6 shadow-[0_20px_50px_-35px_rgba(0,0,0,0.5)] ring-1 ring-black/5">
                        <div class="text-sm text-slate-500">{{ t.howItWorks }}</div>
                        <ol class="mt-4 space-y-3 text-sm text-slate-700">
                            <li class="flex items-start gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-amber-500"></span>
                                {{ t.step1 }}
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                {{ t.step2 }}
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-slate-900"></span>
                                {{ t.step3 }}
                            </li>
                        </ol>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-white/80 p-5 ring-1 ring-black/5">
                            <div class="text-xs text-slate-500">{{ t.studentsLabel }}</div>
                            <div class="mt-2 font-semibold">{{ t.studentsTitle }}</div>
                            <div class="mt-2 text-sm text-slate-600">{{ t.studentsText }}</div>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-5 ring-1 ring-black/5">
                            <div class="text-xs text-slate-500">{{ t.teachersLabel }}</div>
                            <div class="mt-2 font-semibold">{{ t.teachersTitle }}</div>
                            <div class="mt-2 text-sm text-slate-600">{{ t.teachersText }}</div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-900 p-5 text-white">
                        <div class="text-xs text-white/60">{{ t.tipLabel }}</div>
                        <div class="mt-2 text-sm">{{ t.tipText }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

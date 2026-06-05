<script setup lang="ts">
import { useLanguage } from '@/composables/useLanguage';

withDefaults(
    defineProps<{
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);

const { locale, setLocale } = useLanguage();
</script>

<template>
    <div
        class="inline-flex items-center gap-1 rounded-xl border border-sidebar-border/70 bg-background/80 p-1 text-xs shadow-sm"
        aria-label="Language"
    >
        <button
            type="button"
            class="rounded-lg px-2.5 py-1.5 font-medium transition"
            :class="locale === 'sk' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-muted-foreground hover:bg-muted'"
            @click="setLocale('sk')"
        >
            SK
        </button>
        <button
            type="button"
            class="rounded-lg px-2.5 py-1.5 font-medium transition"
            :class="locale === 'en' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-muted-foreground hover:bg-muted'"
            @click="setLocale('en')"
        >
            EN
        </button>
    </div>
</template>

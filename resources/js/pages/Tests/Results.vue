<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

type Result = {
  id: number
  attempt: number
  score: number
  max_score: number
  percent: number
  passed: boolean
  created_at: string | null
}

defineProps<{
  course: { id: number; title: string }
  test: {
    id: number
    title: string
    pass_percent: number
    time_limit_minutes?: number | null
    max_attempts?: number | null
    cooldown_minutes?: number | null
  }
  results: {
    latest: Result | null
    history: Result[]
    latest_details: {
      id: number
      text: string
      is_correct: boolean
      options: {
        id: number
        text: string
        was_selected: boolean
        is_correct: boolean
      }[]
    }[]
  }
}>()
</script>

<template>
  <Head :title="`Výsledky: ${test.title}`" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(34,197,94,0.12),transparent_60%)]"></div>

    <div class="mx-auto max-w-4xl px-6 py-10">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <Link :href="`/courses/${course.id}`" class="text-slate-500 hover:text-slate-800">
          ← Späť ku kurzu
        </Link>
        <Link :href="`/courses/${course.id}/test`" class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
          Spustiť test
        </Link>
      </div>

      <h1 class="mt-4 text-3xl font-display">Výsledky testu</h1>
      <p class="mt-1 text-slate-500">{{ test.title }} · {{ course.title }}</p>

      <div class="mt-6 grid gap-4 md:grid-cols-[1.2fr_1fr]">
        <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
          <div class="text-xs uppercase tracking-wide text-slate-500">Posledný výsledok</div>
          <div v-if="results.latest" class="mt-4">
            <div class="text-4xl font-semibold">
              {{ results.latest.score }} / {{ results.latest.max_score }}
            </div>
            <div class="mt-2 text-lg text-slate-600">{{ results.latest.percent }}%</div>
            <div
              class="mt-3 inline-flex rounded-full px-3 py-1 text-sm"
              :class="results.latest.passed ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
            >
              {{ results.latest.passed ? 'Splnené' : 'Nesplnené' }}
            </div>
            <div v-if="results.latest.created_at" class="mt-3 text-sm text-slate-400">
              {{ results.latest.created_at }}
            </div>
          </div>
          <div v-else class="mt-4 text-sm text-slate-500">
            Tento test ešte nemá žiadny výsledok.
          </div>
        </div>

        <div class="rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
          <div class="text-xs uppercase tracking-wide text-slate-500">Nastavenia</div>
          <div class="mt-4 space-y-2 text-sm text-slate-700">
            <div>Hranica úspešnosti: <span class="font-semibold">{{ test.pass_percent }}%</span></div>
            <div v-if="test.time_limit_minutes">Časový limit: <span class="font-semibold">{{ test.time_limit_minutes }} min</span></div>
            <div v-if="test.max_attempts">Max. pokusov: <span class="font-semibold">{{ test.max_attempts }}</span></div>
            <div v-if="test.cooldown_minutes">Pauza medzi pokusmi: <span class="font-semibold">{{ test.cooldown_minutes }} min</span></div>
          </div>
        </div>
      </div>

      <div class="mt-4 rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
        <div class="text-xs uppercase tracking-wide text-slate-500">História pokusov</div>
        <div v-if="results.history.length === 0" class="mt-3 text-sm text-slate-500">
          Zatiaľ nie sú uložené žiadne pokusy.
        </div>
        <div v-else class="mt-3 space-y-2">
          <div
            v-for="result in results.history"
            :key="result.id"
            class="flex flex-wrap items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700"
          >
            <div class="font-medium">Pokus #{{ result.attempt }}</div>
            <div>{{ result.score }} / {{ result.max_score }}</div>
            <div>{{ result.percent }}%</div>
            <div
              class="rounded-full px-2 py-0.5 text-xs"
              :class="result.passed ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
            >
              {{ result.passed ? 'Splnené' : 'Nesplnené' }}
            </div>
            <div v-if="result.created_at" class="text-xs text-slate-400">
              {{ result.created_at }}
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="results.latest_details.length > 0"
        class="mt-4 rounded-3xl bg-white/85 p-6 ring-1 ring-black/5"
      >
        <div class="text-xs uppercase tracking-wide text-slate-500">Rozbor posledného pokusu</div>
        <div class="mt-4 space-y-3">
          <div
            v-for="detail in results.latest_details"
            :key="detail.id"
            class="rounded-2xl border p-4"
            :class="detail.is_correct ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50'"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="font-medium">{{ detail.text }}</div>
              <div
                class="rounded-full px-3 py-1 text-xs"
                :class="detail.is_correct ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
              >
                {{ detail.is_correct ? 'Správne' : 'Nesprávne' }}
              </div>
            </div>
            <div class="mt-3 space-y-2">
              <div
                v-for="option in detail.options"
                :key="option.id"
                class="rounded-xl bg-white px-3 py-2 text-sm ring-1"
                :class="option.is_correct ? 'ring-emerald-200 text-emerald-800' : option.was_selected ? 'ring-rose-200 text-rose-800' : 'ring-slate-100 text-slate-600'"
              >
                <span v-if="option.was_selected" class="font-semibold">Vybrané: </span>
                <span v-if="option.is_correct" class="font-semibold">Správne: </span>
                {{ option.text }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

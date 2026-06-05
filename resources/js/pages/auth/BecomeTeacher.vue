<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'

const form = useForm({ code: '' })

function submit() {
  form.post('/teacher/activate')
}
</script>

<template>
  <Head title="Aktivovať učiteľa" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(56,189,248,0.12),transparent_60%)]"></div>

    <div class="max-w-md mx-auto px-6 py-10">
      <Link href="/dashboard" class="text-slate-500 hover:text-slate-800">← Späť</Link>

      <h1 class="mt-4 text-3xl font-display">Aktivovať učiteľa</h1>
      <p class="mt-2 text-slate-600">Zadajte kód od administrátora.</p>

      <form @submit.prevent="submit" class="mt-6 space-y-4 rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
        <div>
          <input
              v-model="form.code"
              type="text"
              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none focus:border-slate-400"
              placeholder="Kód učiteľa"
          />
          <div v-if="form.errors.code" class="text-rose-600 text-sm mt-2">
            {{ form.errors.code }}
          </div>
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-slate-900 text-white hover:bg-slate-800 px-4 py-3"
            :disabled="form.processing"
        >
          Aktivovať
        </button>
      </form>
    </div>
  </div>
</template>

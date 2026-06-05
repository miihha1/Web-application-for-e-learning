<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

defineProps<{
  course: { id: number; title: string }
  can_manage: boolean
}>()
</script>

<template>
  <Head :title="`Test • ${course.title}`" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(56,189,248,0.12),transparent_60%)]"></div>

    <div class="max-w-4xl mx-auto px-6 py-10">
      <Link :href="`/courses/${course.id}`" class="text-slate-500 hover:text-slate-800">
        ← Späť ku kurzu
      </Link>

      <div class="mt-6 rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
        <h1 class="text-2xl font-display">Test ešte nie je pripravený</h1>
        <p class="mt-2 text-slate-600">
          V tomto kurze zatiaľ nie sú pridané žiadne otázky.
        </p>

        <Link
          v-if="can_manage"
          :href="`/teacher/courses/${course.id}/manage`"
          class="mt-4 inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2 text-white hover:bg-slate-800 transition"
        >
          Otvoriť správu →
        </Link>
      </div>
    </div>
  </div>
</template>

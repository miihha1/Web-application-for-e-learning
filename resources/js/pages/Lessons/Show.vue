<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

defineProps<{
  course: { id:number; title:string }
  lesson: {
    id:number
    title:string
    content?:string|null
    order:number
    materials: {
      id:number
      title:string
      type:'file'|'link'|'video'
      url?:string|null
      file_url?:string|null
      original_name?:string|null
    }[]
  }
}>()
</script>

<template>
  <Head :title="lesson.title" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(80%_80%_at_20%_10%,rgba(56,189,248,0.12),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(251,191,36,0.18),transparent_60%)]"></div>

    <div class="max-w-4xl mx-auto px-6 py-10">
      <Link
          :href="`/courses/${course.id}`"
          class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-800"
      >
        ← Späť ku kurzu
      </Link>

      <h1 class="mt-4 text-3xl font-display">
        {{ lesson.order }}. {{ lesson.title }}
      </h1>
      <p class="mt-2 text-sm text-slate-500">Kurz: {{ course.title }}</p>

      <div class="mt-6 rounded-3xl bg-white/85 ring-1 ring-black/5 p-7 shadow-[0_25px_60px_-45px_rgba(0,0,0,0.5)]">
        <div v-if="lesson.content" class="formatted-content" v-html="lesson.content" />
        <p v-else class="text-slate-500">
          V tejto lekcii zatiaľ nie je obsah.
        </p>
      </div>

      <div v-if="lesson.materials.length > 0" class="mt-6 rounded-3xl bg-white/85 ring-1 ring-black/5 p-7">
        <div class="text-sm font-medium">Doplnkové materiály</div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <a
            v-for="material in lesson.materials"
            :key="material.id"
            :href="material.file_url || material.url || '#'"
            target="_blank"
            rel="noopener noreferrer"
            class="rounded-2xl bg-slate-50 p-4 ring-1 ring-black/5 hover:bg-slate-100"
          >
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ material.type }}</div>
            <div class="mt-1 font-medium">{{ material.title }}</div>
            <div class="mt-1 break-all text-sm text-slate-500">
              {{ material.original_name || material.url }}
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

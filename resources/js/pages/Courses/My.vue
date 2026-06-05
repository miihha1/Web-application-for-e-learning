<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

type Course = {
  id: number
  title: string
  description?: string | null
  lessons_count?: number
  completed_lessons_count?: number
  progress_percent?: number
  is_public: boolean
  teacher_name?: string | null
  cover_image_url?: string | null
}

const props = defineProps<{
  courses: Course[]
}>()

const page = usePage()
const userRole = computed(() => (page.props.auth?.user as { role?: string } | undefined)?.role ?? '')
const isManagerView = computed(() => userRole.value === 'teacher' || userRole.value === 'admin')

const search = ref('')
const filter = ref<'all' | 'in_progress' | 'completed' | 'not_started'>('all')

function excerpt(html?: string | null) {
  if (!html) return 'Bez popisu'

  if (typeof window === 'undefined') {
    return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  }

  const div = document.createElement('div')
  div.innerHTML = html
  return div.textContent?.replace(/\s+/g, ' ').trim() || 'Bez popisu'
}

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  const searched = props.courses.filter(c => !q || c.title.toLowerCase().includes(q))

  if (isManagerView.value) {
    return searched
  }

  return searched.filter(c => {
    if (filter.value === 'all') return true
    if (filter.value === 'completed') return (c.progress_percent ?? 0) >= 100
    if (filter.value === 'not_started') return (c.progress_percent ?? 0) === 0
    return (c.progress_percent ?? 0) > 0 && (c.progress_percent ?? 0) < 100
  })
})
</script>

<template>
  <Head title="Moje kurzy" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_5%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(60%_80%_at_90%_20%,rgba(14,165,233,0.12),transparent_60%)]"></div>

    <div class="mx-auto max-w-6xl px-6 py-10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <Link href="/courses" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-800 transition">
            ← Späť ku kurzom
          </Link>
          <h1 class="mt-3 text-3xl font-display">Moje kurzy</h1>
          <p class="mt-2 text-slate-600 max-w-2xl">
            {{ isManagerView ? 'Zoznam kurzov, ktoré spravujete alebo ste vytvorili.' : 'Prehľad vašich kurzov, progresu a rýchly prístup k lekciám.' }}
          </p>
        </div>

        <div class="rounded-2xl bg-white/70 ring-1 ring-black/5 px-4 py-3 text-sm text-slate-600">
          Spolu: <span class="font-semibold text-slate-900">{{ filtered.length }}</span>
        </div>
      </div>

      <div class="mt-6 grid gap-4" :class="isManagerView ? 'lg:grid-cols-[1fr]' : 'lg:grid-cols-[1fr_220px]'">
        <div v-if="!isManagerView">
          <div class="flex flex-wrap gap-2">
            <button
              class="rounded-full px-4 py-2 text-sm"
              :class="filter === 'all' ? 'bg-slate-900 text-white' : 'bg-white/80 ring-1 ring-black/5 text-slate-700'"
              @click="filter = 'all'"
            >
              Všetky
            </button>
            <button
              class="rounded-full px-4 py-2 text-sm"
              :class="filter === 'in_progress' ? 'bg-slate-900 text-white' : 'bg-white/80 ring-1 ring-black/5 text-slate-700'"
              @click="filter = 'in_progress'"
            >
              V procese
            </button>
            <button
              class="rounded-full px-4 py-2 text-sm"
              :class="filter === 'completed' ? 'bg-slate-900 text-white' : 'bg-white/80 ring-1 ring-black/5 text-slate-700'"
              @click="filter = 'completed'"
            >
              Dokončené
            </button>
            <button
              class="rounded-full px-4 py-2 text-sm"
              :class="filter === 'not_started' ? 'bg-slate-900 text-white' : 'bg-white/80 ring-1 ring-black/5 text-slate-700'"
              @click="filter = 'not_started'"
            >
              Nezačaté
            </button>
          </div>
        </div>

        <div class="rounded-2xl bg-white/80 ring-1 ring-black/5 p-3">
          <input
            v-model="search"
            type="text"
            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-slate-900 outline-none focus:border-slate-400"
            placeholder="Hľadať kurz..."
          />
        </div>
      </div>

      <div v-if="filtered.length === 0" class="mt-8 rounded-3xl bg-white/80 ring-1 ring-black/5 p-6">
        Pre zvolený filter nie sú žiadne kurzy.
      </div>

      <div v-else class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <Link
            v-for="c in filtered"
            :key="c.id"
            :href="`/courses/${c.id}`"
            class="group rounded-3xl bg-white/90 ring-1 ring-black/5 p-6 transition hover:-translate-y-0.5 hover:shadow-[0_25px_60px_-45px_rgba(0,0,0,0.6)]"
        >
          <div v-if="c.cover_image_url" class="-mx-6 -mt-6 mb-5 overflow-hidden rounded-t-3xl">
            <img :src="c.cover_image_url" :alt="c.title" class="h-44 w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
          </div>
          <div class="flex items-start justify-between">
            <div class="text-lg font-semibold text-slate-900 group-hover:text-slate-800">
              {{ c.title }}
            </div>
            <div v-if="!isManagerView" class="rounded-full bg-emerald-100 text-emerald-800 px-2 py-1 text-xs">
              {{ c.progress_percent ?? 0 }}%
            </div>
          </div>
          <div class="mt-3 text-sm text-slate-600 line-clamp-3">
            {{ excerpt(c.description) }}
          </div>
          <div class="mt-4 text-xs text-slate-500">
            <template v-if="isManagerView">
              Lekcií: {{ c.lessons_count ?? 0 }}
            </template>
            <template v-else>
              Lekcií: {{ c.completed_lessons_count ?? 0 }} / {{ c.lessons_count ?? 0 }}
            </template>
            <span v-if="c.teacher_name"> • Autor: {{ c.teacher_name }}</span>
          </div>
          <div class="mt-4 text-sm font-medium text-slate-700">
            {{ isManagerView ? 'Otvoriť kurz ->' : 'Pokračovať ->' }}
          </div>
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link, Head, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

type Course = {
  id: number
  title: string
  description?: string | null
  lessons_count?: number
  is_public: boolean
  teacher_name?: string | null
  cover_image_url?: string | null
}

const props = defineProps<{
  courses: Course[]
  enrolled_courses: Course[]
}>()

const page = usePage()
const userRole = computed(() => (page.props.auth?.user as { role?: string } | undefined)?.role ?? '')
const myCoursesLabel = computed(() => {
  if (userRole.value === 'teacher' || userRole.value === 'admin') {
    return 'Moje vytvorené kurzy'
  }

  return 'Moje kurzy'
})

const search = ref('')

function excerpt(html?: string | null) {
  if (!html) return 'Bez popisu'

  if (typeof window === 'undefined') {
    return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  }

  const div = document.createElement('div')
  div.innerHTML = html
  return div.textContent?.replace(/\s+/g, ' ').trim() || 'Bez popisu'
}

const filteredCourses = computed(() => {
  const q = search.value.trim().toLowerCase()
  return props.courses.filter(c => !q || c.title.toLowerCase().includes(q))
})

const filteredEnrolled = computed(() => {
  const q = search.value.trim().toLowerCase()
  return props.enrolled_courses.filter(c => !q || c.title.toLowerCase().includes(q))
})
</script>

<template>
  <Head title="Kurzy" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_5%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(60%_80%_at_90%_20%,rgba(14,165,233,0.12),transparent_60%)]"></div>

    <div class="mx-auto max-w-6xl px-6 py-10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <Link
              href="/"
              class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-800 transition"
          >
            ← Späť na úvod
          </Link>
          <h1 class="mt-3 text-3xl font-display">Kurzy</h1>
          <p class="mt-2 text-slate-600 max-w-2xl">
            Vyberte si kurz a prechádzajte lekcie krok za krokom. Na konci je záverečný test.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <Link
            v-if="userRole"
            href="/my-courses"
            class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm text-white transition hover:bg-slate-800"
          >
            {{ myCoursesLabel }}
          </Link>

          <div class="rounded-2xl bg-white/70 ring-1 ring-black/5 px-4 py-3 text-sm text-slate-600">
            Spolu: <span class="font-semibold text-slate-900">{{ filteredCourses.length }}</span>
          </div>
        </div>
      </div>

      <div class="mt-6 rounded-2xl bg-white/80 ring-1 ring-black/5 p-3">
        <input
          v-model="search"
          type="text"
          class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-slate-900 outline-none focus:border-slate-400"
          placeholder="Hľadať kurz podľa názvu..."
        />
      </div>

      <div v-if="filteredEnrolled.length" class="mt-8">
        <div class="text-sm uppercase tracking-wide text-slate-500">Moje kurzy</div>
        <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          <Link
              v-for="c in filteredEnrolled"
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
              <div class="rounded-full bg-emerald-100 text-emerald-800 px-2 py-1 text-xs">
                Zapísaný
              </div>
            </div>
            <div class="mt-3 text-sm text-slate-600 line-clamp-3">
              {{ excerpt(c.description) }}
            </div>
            <div class="mt-4 text-xs text-slate-500">
              Lekcií: {{ c.lessons_count ?? 0 }}
              <span v-if="c.teacher_name"> • Autor: {{ c.teacher_name }}</span>
            </div>
            <div class="mt-4 text-sm font-medium text-slate-700">
              Pokračovať ->
            </div>
          </Link>
        </div>
      </div>

      <div v-if="filteredCourses.length === 0" class="mt-8 rounded-3xl bg-white/80 ring-1 ring-black/5 p-6">
        Pre váš dopyt nie sú žiadne kurzy.
      </div>

      <div v-else class="mt-8">
        <div class="text-sm uppercase tracking-wide text-slate-500">Všetky kurzy</div>
        <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          <Link
              v-for="c in filteredCourses"
              :key="c.id"
              :href="`/courses/${c.id}`"
              class="group rounded-3xl bg-white/80 ring-1 ring-black/5 p-6 transition hover:-translate-y-0.5 hover:shadow-[0_25px_60px_-45px_rgba(0,0,0,0.6)]"
          >
            <div v-if="c.cover_image_url" class="-mx-6 -mt-6 mb-5 overflow-hidden rounded-t-3xl">
              <img :src="c.cover_image_url" :alt="c.title" class="h-44 w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
            </div>
            <div class="flex items-start justify-between">
              <div class="text-lg font-semibold text-slate-900 group-hover:text-slate-800">
                {{ c.title }}
              </div>
              <div
                class="rounded-full px-2 py-1 text-xs"
                :class="c.is_public ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
              >
                {{ c.is_public ? 'Otvorený' : 'S kódom' }}
              </div>
            </div>
            <div class="mt-3 text-sm text-slate-600 line-clamp-3">
              {{ excerpt(c.description) }}
            </div>
            <div class="mt-4 text-xs text-slate-500">
              Lekcií: {{ c.lessons_count ?? 0 }}
              <span v-if="c.teacher_name"> • Autor: {{ c.teacher_name }}</span>
            </div>
            <div class="mt-4 text-sm font-medium text-slate-700">
              Otvoriť kurz ->
            </div>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

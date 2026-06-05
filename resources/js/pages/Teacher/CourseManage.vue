<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { prepareImageUpload } from '@/lib/image-upload'

type Lesson = { id: number; title: string; order: number }
type Option = { id: number; text: string; is_correct: boolean }
type Question = { id: number; text: string; order: number; options: Option[] }
type Test = {
  id: number
  title: string
  description?: string | null
  pass_percent: number
  time_limit_minutes?: number | null
  max_attempts?: number | null
  cooldown_minutes?: number | null
  randomize_questions?: boolean
  randomize_options?: boolean
  questions: Question[]
}
type Analytics = {
  enrolled_count: number
  test_attempts_count: number
  students_attempted_count: number
  average_percent: number | null
  students: {
    id: number
    name: string
    email: string
    completed_lessons: number
    completed_lesson_titles: string[]
    lessons_count: number
    test_attempted: boolean
    test_percent: number | null
    test_passed: boolean | null
    test_attempt: number | null
  }[]
  wrong_questions: {
    id: number
    text: string
    wrong_count: number
    answered_count: number
  }[]
}
type Course = {
  id: number
  title: string
  description?: string | null
  enroll_code?: string | null
  is_public?: boolean
  cover_image_url?: string | null
}

const props = defineProps<{
  course: Course
  lessons: Lesson[]
  test: Test | null
  analytics: Analytics
}>()

const courseForm = useForm({
  title: props.course.title,
  description: props.course.description ?? '',
  cover_image: null as File | null,
  remove_cover_image: false,
})

const testForm = useForm({
  title: props.test?.title ?? 'Záverečný test',
  description: props.test?.description ?? '',
  pass_percent: props.test?.pass_percent ?? 60,
  time_limit_minutes: (props.test?.time_limit_minutes ?? null) as number | null,
  max_attempts: (props.test?.max_attempts ?? null) as number | null,
  cooldown_minutes: props.test?.cooldown_minutes ?? 0,
  randomize_questions: props.test?.randomize_questions ?? false,
  randomize_options: props.test?.randomize_options ?? false,
})

const accessForm = useForm({ is_public: props.course.is_public ?? false })
const regenForm = useForm({})
const accessChanged = computed(() => accessForm.is_public !== (props.course.is_public ?? false))
const canRegenerateCode = computed(() => !accessChanged.value && !accessForm.is_public)
const coverPreview = computed(() => (courseForm.cover_image ? URL.createObjectURL(courseForm.cover_image) : props.course.cover_image_url ?? null))

async function onCoverSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  courseForm.cover_image = file ? await prepareImageUpload(file) : null
  if (courseForm.cover_image) {
    courseForm.remove_cover_image = false
  }
}

function updateCourse() {
  courseForm
    .transform(data => ({
      ...data,
      _method: 'put',
    }))
    .post(`/teacher/courses/${props.course.id}`, {
      forceFormData: true,
    })
}

function updateTest() {
  testForm.put(`/teacher/courses/${props.course.id}/test`)
}

function deleteLesson(lessonId: number) {
  testForm.delete(`/teacher/courses/${props.course.id}/lessons/${lessonId}`)
}

function deleteQuestion(questionId: number) {
  testForm.delete(`/teacher/courses/${props.course.id}/test/questions/${questionId}`)
}

function updateAccess() {
  accessForm.put(`/teacher/courses/${props.course.id}/access`)
}

function regenerateCode() {
  if (!canRegenerateCode.value) return
  regenForm.post(`/teacher/courses/${props.course.id}/access/regenerate`)
}
</script>

<template>
  <Head :title="`Správa: ${course.title}`" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(56,189,248,0.12),transparent_60%)]"></div>

    <div class="max-w-6xl mx-auto px-6 py-10">
      <Link :href="`/courses/${course.id}`" class="text-slate-500 hover:text-slate-800">← Späť ku kurzu</Link>

      <div class="mt-4 flex flex-col gap-4">
        <h1 class="text-3xl font-display">Správa kurzu: {{ course.title }}</h1>
      </div>

      <div class="mt-6 rounded-3xl bg-slate-950 p-6 text-white ring-1 ring-black/10">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div class="text-sm uppercase tracking-wide text-slate-300">Štatistika kurzu</div>
            <h2 class="mt-1 text-2xl font-semibold">Prehľad študentov a testov</h2>
          </div>
          <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm">
            Zapísaní študenti: <span class="font-semibold">{{ analytics.enrolled_count }}</span>
          </div>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-4">
          <div class="rounded-2xl bg-white/10 p-4">
            <div class="text-xs uppercase text-slate-300">Pokusy testu</div>
            <div class="mt-2 text-2xl font-semibold">{{ analytics.test_attempts_count }}</div>
          </div>
          <div class="rounded-2xl bg-white/10 p-4">
            <div class="text-xs uppercase text-slate-300">Študenti s testom</div>
            <div class="mt-2 text-2xl font-semibold">{{ analytics.students_attempted_count }}</div>
          </div>
          <div class="rounded-2xl bg-white/10 p-4 md:col-span-2">
            <div class="text-xs uppercase text-slate-300">Priemerná úspešnosť</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ analytics.average_percent === null ? '—' : `${analytics.average_percent}%` }}
            </div>
          </div>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-[1.5fr_1fr]">
          <div class="overflow-hidden rounded-2xl bg-white text-slate-900">
            <div class="grid grid-cols-[1.3fr_0.8fr_0.8fr] gap-3 border-b border-slate-100 px-4 py-3 text-xs uppercase text-slate-500">
              <div>Študent</div>
              <div>Lekcie</div>
              <div>Test</div>
            </div>
            <div v-if="analytics.students.length === 0" class="px-4 py-5 text-sm text-slate-500">
              Zatiaľ nie sú zapísaní žiadni študenti.
            </div>
            <div
              v-for="student in analytics.students"
              :key="student.id"
              class="grid grid-cols-[1.3fr_0.8fr_0.8fr] gap-3 border-b border-slate-100 px-4 py-3 text-sm last:border-b-0"
            >
              <div>
                <div class="font-medium">{{ student.name }}</div>
                <div class="text-xs text-slate-500">{{ student.email }}</div>
              </div>
              <div>
                <div>{{ student.completed_lessons }} / {{ student.lessons_count }}</div>
                <div v-if="student.completed_lesson_titles.length > 0" class="mt-1 text-xs text-slate-500">
                  {{ student.completed_lesson_titles.join(', ') }}
                </div>
              </div>
              <div>
                <span v-if="student.test_attempted">
                  {{ student.test_percent }}%
                  <span :class="student.test_passed ? 'text-emerald-700' : 'text-rose-700'">
                    {{ student.test_passed ? 'splnené' : 'nesplnené' }}
                  </span>
                </span>
                <span v-else class="text-slate-400">Bez pokusu</span>
              </div>
            </div>
          </div>

          <div class="rounded-2xl bg-white p-4 text-slate-900">
            <div class="text-sm font-medium">Najčastejšie nesprávne otázky</div>
            <div v-if="analytics.wrong_questions.length === 0" class="mt-3 text-sm text-slate-500">
              Zatiaľ nie sú dostupné odpovede.
            </div>
            <div v-for="question in analytics.wrong_questions" :key="question.id" class="mt-3 rounded-xl bg-slate-50 p-3">
              <div class="text-sm font-medium">{{ question.text }}</div>
              <div class="mt-1 text-xs text-slate-500">
                Nesprávne: {{ question.wrong_count }} / {{ question.answered_count }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
        <div class="text-sm font-medium">Detaily kurzu</div>

        <form @submit.prevent="updateCourse" class="mt-4 space-y-5">
          <div>
            <label class="text-sm text-slate-600">Názov kurzu</label>
            <input v-model="courseForm.title" type="text" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3" />
            <div v-if="courseForm.errors.title" class="text-rose-600 text-sm mt-2">{{ courseForm.errors.title }}</div>
          </div>

          <div>
            <label class="text-sm text-slate-600">Titulný obrázok</label>
            <input type="file" accept="image/*" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700" @change="onCoverSelected" />
            <div v-if="courseForm.errors.cover_image" class="text-rose-600 text-sm mt-2">{{ courseForm.errors.cover_image }}</div>

            <div v-if="coverPreview" class="mt-4 overflow-hidden rounded-[1.5rem] border border-slate-200 bg-[#fbfaf7]">
              <img :src="coverPreview" alt="Titulný obrázok kurzu" class="h-60 w-full object-cover" />
            </div>

            <label v-if="coverPreview" class="mt-3 flex items-center gap-2 text-sm text-slate-700">
              <input v-model="courseForm.remove_cover_image" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
              Odstrániť aktuálny obrázok
            </label>
          </div>

          <div>
            <label class="text-sm text-slate-600">Popis kurzu</label>
            <div class="mt-2">
              <RichTextEditor v-model="courseForm.description" upload-url="/teacher/uploads/images" min-height-class="min-h-[240px]" />
            </div>
            <div v-if="courseForm.errors.description" class="text-rose-600 text-sm mt-2">{{ courseForm.errors.description }}</div>
          </div>

          <button class="rounded-xl bg-slate-900 px-5 py-3 text-white">Uložiť detaily</button>
        </form>
      </div>

      <div class="mt-6 rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
        <div class="text-sm font-medium">Prístup ku kurzu</div>
        <form @submit.prevent="updateAccess" class="mt-3 flex flex-col gap-3">
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="radio" :value="true" v-model="accessForm.is_public" class="h-4 w-4 accent-slate-900" />
            Otvorený prístup (bez kódu)
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="radio" :value="false" v-model="accessForm.is_public" class="h-4 w-4 accent-slate-900" />
            Prístup s kódom
          </label>
          <button class="mt-2 rounded-xl bg-slate-900 px-4 py-2 text-white">Uložiť prístup</button>
        </form>

        <div v-if="!accessForm.is_public" class="mt-4 rounded-2xl bg-[#f8fafc] ring-1 ring-black/5 p-4">
          <div class="text-xs uppercase tracking-wide text-slate-500">Kód kurzu</div>
          <div class="mt-2 text-lg font-semibold tracking-widest">{{ course.enroll_code || '—' }}</div>
          <div v-if="accessChanged" class="mt-2 text-sm text-amber-700">
            Najprv ulož zmenu prístupu, potom bude možné vygenerovať nový kód.
          </div>
          <button
            type="button"
            :disabled="!canRegenerateCode || regenForm.processing"
            class="mt-3 rounded-xl ring-1 ring-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
            @click="regenerateCode"
          >
            Vygenerovať nový kód
          </button>
        </div>
      </div>

      <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
          <div class="flex items-center justify-between">
            <div class="text-sm font-medium">Lekcie</div>
            <Link :href="`/teacher/courses/${course.id}/lessons/create`" class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">
              Nová lekcia
            </Link>
          </div>

          <div class="mt-5 space-y-2">
            <div
              v-for="l in lessons"
              :key="l.id"
              class="flex items-center justify-between rounded-xl bg-[#f8fafc] ring-1 ring-black/5 px-4 py-3 text-sm"
            >
              <div>
                <span class="text-slate-400 mr-2">{{ l.order }}.</span>
                <span class="font-medium">{{ l.title }}</span>
              </div>
              <div class="flex items-center gap-2">
                <Link :href="`/teacher/courses/${course.id}/lessons/${l.id}/edit`" class="text-slate-600 hover:text-slate-900">
                  Upraviť
                </Link>
                <button class="text-rose-600" @click="deleteLesson(l.id)">Odstrániť</button>
              </div>
            </div>

            <div v-if="lessons.length === 0" class="rounded-2xl bg-[#f8fafc] ring-1 ring-black/5 p-4 text-sm text-slate-500">
              Zatiaľ nie sú vytvorené žiadne lekcie.
            </div>
          </div>
        </div>

        <div class="rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
          <div class="text-sm font-medium">Test</div>

          <form @submit.prevent="updateTest" class="mt-4 space-y-3">
            <input v-model="testForm.title" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2" placeholder="Názov testu" />
            <textarea v-model="testForm.description" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2" placeholder="Popis testu"></textarea>
            <input v-model="testForm.pass_percent" type="number" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2" placeholder="Percento úspešnosti" />
            <div class="grid gap-3 sm:grid-cols-3">
              <input v-model="testForm.time_limit_minutes" type="number" min="1" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2" placeholder="Limit minút" />
              <input v-model="testForm.max_attempts" type="number" min="1" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2" placeholder="Max. pokusov" />
              <input v-model="testForm.cooldown_minutes" type="number" min="0" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2" placeholder="Pauza minút" />
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
              <label class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <input v-model="testForm.randomize_questions" type="checkbox" class="h-4 w-4 accent-slate-900" />
                Náhodné poradie otázok
              </label>
              <label class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <input v-model="testForm.randomize_options" type="checkbox" class="h-4 w-4 accent-slate-900" />
                Náhodné poradie odpovedí
              </label>
            </div>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-white">Uložiť test</button>
          </form>

          <div class="mt-5">
            <div class="flex items-center justify-between">
              <div class="text-sm font-medium">Otázky</div>
              <Link :href="`/teacher/courses/${course.id}/test/questions/create`" class="text-slate-600 hover:text-slate-900">
                Pridať otázku ->
              </Link>
            </div>

            <div class="mt-3 space-y-2">
              <div
                v-for="q in (test?.questions ?? [])"
                :key="q.id"
                class="rounded-2xl bg-[#f8fafc] ring-1 ring-black/5 px-4 py-4 text-sm"
              >
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div class="font-semibold text-slate-950">{{ q.order }}. {{ q.text }}</div>
                  <div class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800">
                    {{ q.options.filter(option => option.is_correct).length }} správne
                  </div>
                </div>
                <ul class="mt-3 space-y-2">
                  <li
                    v-for="o in q.options"
                    :key="o.id"
                    class="flex items-start justify-between gap-3 rounded-xl px-3 py-2 ring-1"
                    :class="o.is_correct ? 'bg-emerald-50 text-emerald-950 ring-emerald-200' : 'bg-white text-slate-600 ring-slate-100'"
                  >
                    <span :class="o.is_correct ? 'font-semibold' : ''">{{ o.text }}</span>
                    <span
                      v-if="o.is_correct"
                      class="shrink-0 rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-semibold text-white"
                    >
                      Správna
                    </span>
                  </li>
                </ul>
                <div class="mt-2 flex items-center gap-2">
                  <Link :href="`/teacher/courses/${course.id}/test/questions/${q.id}/edit`" class="text-slate-600 hover:text-slate-900">
                    Upraviť
                  </Link>
                  <button class="text-rose-600" @click="deleteQuestion(q.id)">Odstrániť</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

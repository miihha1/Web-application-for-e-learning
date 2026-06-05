<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

type Lesson = {
  id: number
  title: string
  order: number
}

type Course = {
  id: number
  title: string
  description?: string | null
  is_public: boolean
  teacher_id?: number | null
  enroll_code?: string | null
  cover_image_url?: string | null
  lessons?: Lesson[]
  test_available?: boolean
}

defineProps<{
  course: Course
  access: {
    can_access: boolean
    is_enrolled: boolean
    can_enroll: boolean
    enroll_requires_code: boolean
    can_manage: boolean
  }
}>()

const enrollForm = useForm({ code: '' })

function enroll(courseId: number) {
  enrollForm.post(`/courses/${courseId}/enroll`)
}

function unenroll(courseId: number) {
  enrollForm.delete(`/courses/${courseId}/enroll`)
}
</script>

<template>
  <Head :title="course.title" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(148,163,184,0.22),transparent_60%),radial-gradient(70%_80%_at_90%_30%,rgba(253,230,138,0.25),transparent_60%)]"></div>

    <div class="mx-auto max-w-6xl px-6 py-10">
      <Link href="/courses" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-800 transition">
        ← Späť ku kurzom
      </Link>

      <div class="mt-4 grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="rounded-3xl bg-white/80 ring-1 ring-black/5 p-7">
          <div v-if="course.cover_image_url" class="-mx-7 -mt-7 mb-6 overflow-hidden rounded-t-3xl bg-[#eef2f7]">
            <img :src="course.cover_image_url" :alt="course.title" class="h-52 w-full object-cover sm:h-60 lg:h-64" />
          </div>
          <h1 class="text-3xl font-display">{{ course.title }}</h1>
          <div v-if="course.description" class="formatted-content mt-3 text-slate-600" v-html="course.description" />
          <p v-else class="mt-3 text-slate-600">Bez popisu</p>

          <div v-if="!access.can_access" class="mt-6 rounded-2xl bg-amber-50 ring-1 ring-amber-200 p-4 text-sm">
            Tento kurz je dostupný iba zapísaným študentom.
          </div>

          <div
            v-if="!access.can_access && access.can_enroll"
            class="mt-6 rounded-2xl bg-white ring-1 ring-black/5 p-5"
          >
            <div class="text-sm font-medium text-slate-900">Zápis do kurzu</div>

            <div v-if="access.enroll_requires_code" class="mt-3">
              <label class="text-xs text-slate-500">Kód kurzu</label>
              <input
                v-model="enrollForm.code"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-slate-900 outline-none focus:border-slate-400"
                placeholder="Zadajte kód"
              />
              <div v-if="enrollForm.errors.code" class="text-rose-600 text-sm mt-2">
                {{ enrollForm.errors.code }}
              </div>
            </div>

            <button
              class="mt-4 inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2 text-white hover:bg-slate-800 transition"
              :disabled="enrollForm.processing"
              @click="enroll(course.id)"
            >
              Zapísať sa
            </button>
          </div>

          <div
            v-else-if="!access.can_access"
            class="mt-6 rounded-2xl bg-white ring-1 ring-black/5 p-5 text-sm text-slate-600"
          >
            K zápisu do kurzu sa musíte prihlásiť ako študent.
          </div>

          <div class="mt-6 rounded-2xl bg-[#f8fafc] ring-1 ring-black/5 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-500">Lekcie</div>

            <div v-if="!access.can_access" class="mt-3 text-slate-500 text-sm">
              Najprv sa zapíšte do kurzu, aby ste videli obsah.
            </div>

            <div v-else-if="!course.lessons || course.lessons.length === 0" class="mt-3 text-slate-500 text-sm">
              Zatiaľ nie sú žiadne lekcie.
            </div>

            <ol v-else class="mt-4 space-y-2">
              <li v-for="l in course.lessons" :key="l.id">
                <Link
                    :href="`/courses/${course.id}/lessons/${l.id}`"
                    class="flex items-center justify-between rounded-xl bg-white ring-1 ring-black/5 px-4 py-3 hover:bg-slate-50 transition"
                >
                  <div class="text-sm">
                    <span class="text-slate-400 mr-2">{{ l.order }}.</span>
                    <span class="font-medium">{{ l.title }}</span>
                  </div>

                  <span class="text-xs text-slate-500">Otvoriť -></span>
                </Link>
              </li>
            </ol>
          </div>
        </div>

        <div class="space-y-4">
          <div class="rounded-3xl bg-white/80 ring-1 ring-black/5 p-6">
            <div class="text-xs uppercase tracking-wide text-slate-500">Zhrnutie</div>
            <div class="mt-3 text-sm text-slate-700">Lekcií: <span class="font-semibold">{{ course.lessons?.length ?? 0 }}</span></div>
            <div class="mt-1 text-sm text-slate-700">Typ: <span class="font-semibold">{{ course.is_public ? 'Otvorený' : 'S kódom' }}</span></div>
          </div>

          <div v-if="course.enroll_code" class="rounded-3xl bg-white/80 ring-1 ring-black/5 p-6">
            <div class="text-xs uppercase tracking-wide text-slate-500">Kód kurzu</div>
            <div class="mt-2 text-lg font-semibold tracking-widest">{{ course.enroll_code }}</div>
            <div class="mt-2 text-sm text-slate-600">Odovzdajte tento kód študentom na zápis.</div>
          </div>

          <div v-if="access.can_manage" class="rounded-3xl bg-white/80 ring-1 ring-black/5 p-6">
            <div class="text-sm text-slate-600">Správa kurzu</div>
            <Link
              :href="`/teacher/courses/${course.id}/manage`"
              class="mt-3 inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2 text-white hover:bg-slate-800 transition"
            >
              Otvoriť správu ->
            </Link>
          </div>

          <div class="rounded-3xl bg-slate-900 text-white p-6">
            <div class="text-sm text-white/70">Záverečný test</div>
            <div class="mt-2 text-lg font-semibold">Overte si vedomosti z kurzu</div>
            <div v-if="course.test_available" class="mt-3 text-sm text-white/70">
              Po dokončení uvidíte výsledok a históriu pokusov.
            </div>
            <div v-else class="mt-3 text-sm text-white/70">
              Test zatiaľ nie je pripravený.
            </div>
            <Link
                v-if="course.test_available"
                :href="`/courses/${course.id}/test`"
                class="mt-5 inline-flex items-center justify-center rounded-xl bg-amber-400 px-5 py-2 text-slate-900 font-medium hover:bg-amber-300 transition"
                :class="!access.can_access ? 'pointer-events-none opacity-60' : ''"
            >
              Spustiť test ->
            </Link>
          </div>

          <div v-if="access.can_enroll && access.can_access" class="rounded-3xl bg-white/80 ring-1 ring-black/5 p-6">
            <div class="text-sm text-slate-600">Zápis do kurzu</div>

            <div v-if="access.enroll_requires_code" class="mt-3">
              <label class="text-xs text-slate-500">Kód kurzu</label>
              <input
                v-model="enrollForm.code"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-slate-900 outline-none focus:border-slate-400"
                placeholder="Zadajte kód"
              />
              <div v-if="enrollForm.errors.code" class="text-rose-600 text-sm mt-2">
                {{ enrollForm.errors.code }}
              </div>
            </div>

            <button
              class="mt-4 inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2 text-white hover:bg-slate-800 transition"
              :disabled="enrollForm.processing"
              @click="enroll(course.id)"
            >
              Zapísať sa
            </button>
          </div>

          <div v-if="access.is_enrolled" class="rounded-3xl bg-white/80 ring-1 ring-black/5 p-6">
            <div class="text-sm text-slate-600">Ste zapísaní</div>
            <button
              class="mt-3 inline-flex items-center justify-center rounded-xl ring-1 ring-slate-300 px-5 py-2 text-slate-700 hover:bg-slate-100 transition"
              :disabled="enrollForm.processing"
              @click="unenroll(course.id)"
            >
              Odhlásiť sa
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

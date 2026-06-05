<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

type Question = {
  id: number
  text: string
  allows_multiple: boolean
  options: { id: number; text: string }[]
}

const props = defineProps<{
  course: { id: number; title: string }
  test: {
    id: number
    title: string
    pass_percent: number
    time_limit_minutes?: number | null
    max_attempts?: number | null
    cooldown_minutes?: number | null
    questions: Question[]
  }
  attempt_policy: {
    attempts_count: number
    can_take_test: boolean
    blocked_reason: string | null
  }
  timing: {
    started_at: string | null
    expires_at: string | null
    server_now: string
  }
}>()

const form = useForm<{ answers: Record<string, number | number[]> }>({
  answers: {},
})
const serverTimeOffset = new Date(props.timing.server_now).getTime() - Date.now()
const remainingSeconds = ref(calculateRemainingSeconds())
const testError = computed(() => (form.errors as Record<string, string>).test)
let timerId: number | undefined

const formattedRemaining = computed(() => {
  const minutes = Math.floor(remainingSeconds.value / 60)
  const seconds = remainingSeconds.value % 60

  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const timerStateClass = computed(() => {
  if (remainingSeconds.value <= 60) return 'bg-rose-100 text-rose-800 ring-rose-200'
  if (remainingSeconds.value <= 300) return 'bg-amber-100 text-amber-800 ring-amber-200'

  return 'bg-emerald-100 text-emerald-800 ring-emerald-200'
})

function calculateRemainingSeconds() {
  if (!props.timing.expires_at) return 0

  return Math.max(0, Math.ceil((new Date(props.timing.expires_at).getTime() - (Date.now() + serverTimeOffset)) / 1000))
}

function submit() {
  if (!props.attempt_policy.can_take_test || form.processing) return

  form.post(`/courses/${props.course.id}/test`)
}

function toggleMultiAnswer(questionId: number, optionId: number, checked: boolean) {
  const key = String(questionId)
  const current = form.answers[key]
  const values = Array.isArray(current) ? [...current] : []

  if (checked && !values.includes(optionId)) {
    values.push(optionId)
  }

  if (!checked) {
    const index = values.indexOf(optionId)
    if (index >= 0) values.splice(index, 1)
  }

  form.answers[key] = values
}

function isMultiAnswerChecked(questionId: number, optionId: number) {
  const answer = form.answers[String(questionId)]

  return Array.isArray(answer) && answer.includes(optionId)
}

onMounted(() => {
  if (!props.timing.expires_at || !props.attempt_policy.can_take_test) return

  timerId = window.setInterval(() => {
    remainingSeconds.value = calculateRemainingSeconds()

    if (remainingSeconds.value <= 0) {
      window.clearInterval(timerId)
      submit()
    }
  }, 1000)
})

onBeforeUnmount(() => {
  if (timerId) window.clearInterval(timerId)
})
</script>

<template>
  <Head :title="test.title" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(34,197,94,0.12),transparent_60%)]"></div>

    <div class="mx-auto max-w-4xl px-6 py-10">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <Link :href="`/courses/${course.id}`" class="text-slate-500 hover:text-slate-800">
          ← Späť ku kurzu
        </Link>
        <Link :href="`/courses/${course.id}/test/results`" class="rounded-xl bg-white px-4 py-2 text-sm text-slate-700 ring-1 ring-black/10 hover:bg-slate-50">
          Výsledky testu
        </Link>
      </div>

      <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-display">{{ test.title }}</h1>
          <p class="mt-1 text-slate-500">Kurz: {{ course.title }}</p>
        </div>
        <div
          v-if="test.time_limit_minutes"
          class="rounded-2xl px-5 py-3 text-center ring-1"
          :class="timerStateClass"
        >
          <div class="text-xs uppercase tracking-wide">Zostáva</div>
          <div class="text-2xl font-semibold tabular-nums">{{ formattedRemaining }}</div>
        </div>
      </div>

      <div class="mt-6 rounded-3xl bg-white/85 p-6 ring-1 ring-black/5">
        <div class="text-xs uppercase tracking-wide text-slate-500">Inštrukcie</div>
        <p class="mt-2 text-sm text-slate-600">
          Pri niektorých otázkach môže byť správnych viac odpovedí. Po odoslaní budete presmerovaní na samostatnú stránku výsledkov.
        </p>
        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-600">
          <span class="rounded-full bg-slate-100 px-3 py-1">Hranica úspešnosti: {{ test.pass_percent }}%</span>
          <span v-if="test.time_limit_minutes" class="rounded-full bg-slate-100 px-3 py-1">Limit: {{ test.time_limit_minutes }} min</span>
          <span v-if="test.max_attempts" class="rounded-full bg-slate-100 px-3 py-1">Pokusy: {{ attempt_policy.attempts_count }} / {{ test.max_attempts }}</span>
          <span v-if="test.cooldown_minutes" class="rounded-full bg-slate-100 px-3 py-1">Pauza: {{ test.cooldown_minutes }} min</span>
        </div>
      </div>

      <div v-if="testError || !attempt_policy.can_take_test" class="mt-4 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
        {{ testError || attempt_policy.blocked_reason }}
      </div>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div
          v-for="q in test.questions"
          :key="q.id"
          class="rounded-3xl bg-white/90 p-6 ring-1 ring-black/5"
        >
          <div class="font-medium text-slate-900">{{ q.text }}</div>
          <div class="mt-2 text-xs text-slate-500">
            {{ q.allows_multiple ? 'Vyberte všetky správne odpovede.' : 'Vyberte jednu správnu odpoveď.' }}
          </div>

          <div class="mt-3 space-y-2">
            <label
              v-for="opt in q.options"
              :key="opt.id"
              class="flex items-center gap-2 text-slate-700"
            >
              <input
                :type="q.allows_multiple ? 'checkbox' : 'radio'"
                :name="`q_${q.id}`"
                :value="opt.id"
                :checked="q.allows_multiple ? isMultiAnswerChecked(q.id, opt.id) : form.answers[String(q.id)] === opt.id"
                class="h-4 w-4 accent-slate-900"
                :disabled="!attempt_policy.can_take_test"
                @change="q.allows_multiple
                  ? toggleMultiAnswer(q.id, opt.id, ($event.target as HTMLInputElement).checked)
                  : (form.answers[String(q.id)] = opt.id)"
              />
              <span>{{ opt.text }}</span>
            </label>
          </div>
        </div>

        <button
          class="rounded-xl bg-slate-900 px-5 py-3 text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="form.processing || !attempt_policy.can_take_test"
        >
          Odoslať test
        </button>
      </form>
    </div>
  </div>
</template>

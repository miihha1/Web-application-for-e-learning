<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps<{
  course: { id: number; title: string }
  test: { id: number; title: string }
  question: null | {
    id: number
    text: string
    options: string[]
    correct_indices: number[]
  }
}>()

const initialOptions = [...(props.question?.options ?? ['', '', '', ''])]
while (initialOptions.length < 2) initialOptions.push('')

const form = useForm({
  text: props.question?.text ?? '',
  options: initialOptions,
  correct_indices: props.question?.correct_indices ?? [0],
})

const canRemoveOption = computed(() => form.options.length > 2)

function addOption() {
  form.options.push('')
}

function removeOption(index: number) {
  if (form.options.length <= 2) return

  form.options.splice(index, 1)
  form.correct_indices = form.correct_indices
    .filter(value => value !== index)
    .map(value => (value > index ? value - 1 : value))

  if (form.correct_indices.length === 0) {
    form.correct_indices = [0]
  }
}

function toggleCorrect(index: number, checked: boolean) {
  if (checked) {
    if (!form.correct_indices.includes(index)) {
      form.correct_indices.push(index)
    }
    return
  }

  form.correct_indices = form.correct_indices.filter(value => value !== index)
}

function submit() {
  if (props.question) {
    form.put(`/teacher/courses/${props.course.id}/test/questions/${props.question.id}`)
  } else {
    form.post(`/teacher/courses/${props.course.id}/test/questions`)
  }
}
</script>

<template>
  <Head :title="props.question ? 'Upraviť otázku' : 'Nová otázka'" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(56,189,248,0.12),transparent_60%)]"></div>

    <div class="max-w-3xl mx-auto px-6 py-10">
      <Link :href="`/teacher/courses/${course.id}/manage`" class="text-slate-500 hover:text-slate-800">← Späť do správy</Link>

      <h1 class="mt-4 text-3xl font-display">{{ props.question ? 'Upraviť otázku' : 'Nová otázka' }}</h1>
      <p class="mt-2 text-slate-600">Kurz: {{ course.title }}</p>

      <form @submit.prevent="submit" class="mt-6 space-y-5 rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
        <div>
          <label class="text-sm text-slate-600">Text otázky</label>
          <input
            v-model="form.text"
            type="text"
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"
            placeholder="Napríklad: Ktoré značky patria do HTML?"
          />
          <div v-if="form.errors.text" class="text-rose-600 text-sm mt-2">
            {{ form.errors.text }}
          </div>
        </div>

        <div class="rounded-2xl bg-[#f8fafc] p-4 text-sm text-slate-600 ring-1 ring-black/5">
          Označte jeden alebo viac správnych variantov. Počet možností si môžete pridávať alebo odoberať.
        </div>

        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-sm text-slate-600">Možnosti odpovede</div>
            <button type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800" @click="addOption">
              Pridať možnosť
            </button>
          </div>

          <div v-for="(opt, idx) in form.options" :key="idx" class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-start gap-3">
              <input
                :checked="form.correct_indices.includes(idx)"
                type="checkbox"
                class="mt-3 h-4 w-4 accent-slate-900"
                @change="toggleCorrect(idx, ($event.target as HTMLInputElement).checked)"
              />

              <div class="flex-1">
                <input
                  v-model="form.options[idx]"
                  type="text"
                  class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3"
                  :placeholder="`Možnosť ${idx + 1}`"
                />
              </div>

              <button
                type="button"
                class="rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!canRemoveOption"
                @click="removeOption(idx)"
              >
                Odstrániť
              </button>
            </div>
          </div>

          <div v-if="form.errors.options" class="text-rose-600 text-sm">
            {{ form.errors.options }}
          </div>
          <div v-if="form.errors.correct_indices" class="text-rose-600 text-sm">
            {{ form.errors.correct_indices }}
          </div>
        </div>

        <button class="rounded-xl bg-slate-900 px-5 py-3 text-white">
          {{ props.question ? 'Uložiť' : 'Vytvoriť' }}
        </button>
      </form>
    </div>
  </div>
</template>

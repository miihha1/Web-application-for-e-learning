<script setup lang="ts">
import RichTextEditor from '@/components/RichTextEditor.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

type Material = {
  id: number
  title: string
  type: 'file' | 'link' | 'video'
  url?: string | null
  file_url?: string | null
  original_name?: string | null
  size?: number | null
}
type Lesson = {
  id: number
  title: string
  content?: string | null
  order: number
  materials?: Material[]
}

const props = defineProps<{
  course: { id: number; title: string }
  lesson: Lesson | null
  suggestedOrder: number
}>()

const isEditing = computed(() => Boolean(props.lesson))

const form = useForm({
  title: props.lesson?.title ?? '',
  content: props.lesson?.content ?? '',
  order: props.lesson?.order ?? props.suggestedOrder,
})

const materialForm = useForm({
  title: '',
  type: 'file' as 'file' | 'link' | 'video',
  file: null as File | null,
  url: '',
})
const maxMaterialFileSize = 2 * 1024 * 1024
const materialFileError = computed(() => {
  if (!materialForm.file) return null

  return materialForm.file.size > maxMaterialFileSize
    ? 'Súbor je príliš veľký. Maximálna veľkosť je 2 MB.'
    : null
})

function submit() {
  if (props.lesson) {
    form.put(`/teacher/courses/${props.course.id}/lessons/${props.lesson.id}`)
    return
  }

  form.post(`/teacher/courses/${props.course.id}/lessons`)
}

function submitMaterial() {
  if (!props.lesson) return
  if (materialFileError.value) return

  materialForm.post(`/teacher/courses/${props.course.id}/lessons/${props.lesson.id}/materials`, {
    forceFormData: true,
    onSuccess: () => {
      materialForm.reset()
      materialForm.type = 'file'
      materialForm.file = null
    },
  })
}

function onMaterialFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  materialForm.file = input.files?.[0] ?? null
}

function deleteMaterial(materialId: number) {
  if (!props.lesson) return

  materialForm.delete(`/teacher/courses/${props.course.id}/lessons/${props.lesson.id}/materials/${materialId}`)
}
</script>

<template>
  <Head :title="isEditing ? `Lekcia: ${lesson?.title}` : 'Nová lekcia'" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(56,189,248,0.12),transparent_60%)]"></div>

    <div class="max-w-5xl mx-auto px-6 py-10">
      <Link :href="`/teacher/courses/${course.id}/manage`" class="text-slate-500 hover:text-slate-800">← Späť do správy</Link>

      <h1 class="mt-4 text-3xl font-display">{{ isEditing ? 'Upraviť lekciu' : 'Vytvoriť lekciu' }}</h1>
      <p class="mt-2 text-slate-600">Kurz: {{ course.title }}</p>

      <form @submit.prevent="submit" class="mt-6 space-y-5 rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
        <div>
          <label class="text-sm text-slate-600">Názov</label>
          <input
            v-model="form.title"
            type="text"
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"
          />
          <div v-if="form.errors.title" class="text-rose-600 text-sm mt-2">
            {{ form.errors.title }}
          </div>
        </div>

        <div>
          <label class="text-sm text-slate-600">Obsah lekcie</label>
          <div class="mt-2">
            <RichTextEditor v-model="form.content" upload-url="/teacher/uploads/images" min-height-class="min-h-[420px]" />
          </div>
          <div v-if="form.errors.content" class="text-rose-600 text-sm mt-2">
            {{ form.errors.content }}
          </div>
        </div>

        <div>
          <label class="text-sm text-slate-600">Poradie</label>
          <input
            v-model="form.order"
            type="number"
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3"
          />
          <div v-if="form.errors.order" class="text-rose-600 text-sm mt-2">
            {{ form.errors.order }}
          </div>
        </div>

        <button class="rounded-xl bg-slate-900 px-5 py-3 text-white">
          {{ isEditing ? 'Uložiť' : 'Vytvoriť lekciu' }}
        </button>
      </form>

      <div v-if="isEditing && lesson" class="mt-6 rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
        <div class="flex flex-col gap-1">
          <div class="text-sm font-medium">Doplnkové materiály</div>
          <p class="text-sm text-slate-500">PDF, ZIP, DOCX, externé odkazy alebo video k lekcii.</p>
        </div>

        <form @submit.prevent="submitMaterial" class="mt-4 grid gap-3 lg:grid-cols-[1fr_180px_1.3fr_auto]">
          <input
            v-model="materialForm.title"
            type="text"
            class="rounded-xl border border-slate-200 bg-white px-4 py-3"
            placeholder="Názov materiálu"
          />
          <select v-model="materialForm.type" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
            <option value="file">Súbor</option>
            <option value="link">Odkaz</option>
            <option value="video">Video</option>
          </select>
          <input
            v-if="materialForm.type === 'file'"
            type="file"
            accept=".pdf,.zip,.doc,.docx"
            class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm"
            @change="onMaterialFileSelected"
          />
          <input
            v-else
            v-model="materialForm.url"
            type="url"
            class="rounded-xl border border-slate-200 bg-white px-4 py-3"
            placeholder="https://..."
          />
          <button
            class="rounded-xl bg-slate-900 px-5 py-3 text-white disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="Boolean(materialFileError) || materialForm.processing"
          >
            Pridať
          </button>
        </form>

        <div v-if="materialForm.errors.title" class="mt-2 text-sm text-rose-600">{{ materialForm.errors.title }}</div>
        <div v-if="materialForm.errors.file" class="mt-2 text-sm text-rose-600">{{ materialForm.errors.file }}</div>
        <div v-if="materialFileError" class="mt-2 text-sm text-rose-600">{{ materialFileError }}</div>
        <div v-if="materialForm.errors.url" class="mt-2 text-sm text-rose-600">{{ materialForm.errors.url }}</div>

        <div class="mt-5 space-y-2">
          <div
            v-for="material in lesson.materials ?? []"
            :key="material.id"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm"
          >
            <div>
              <div class="font-medium">{{ material.title }}</div>
              <a
                :href="material.file_url || material.url || '#'"
                target="_blank"
                rel="noopener noreferrer"
                class="text-slate-500 hover:text-slate-900"
              >
                {{ material.original_name || material.url || material.type }}
              </a>
            </div>
            <button class="text-rose-600" @click="deleteMaterial(material.id)">
              Odstrániť
            </button>
          </div>
          <div v-if="(lesson.materials ?? []).length === 0" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
            Zatiaľ nie sú pridané žiadne materiály.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

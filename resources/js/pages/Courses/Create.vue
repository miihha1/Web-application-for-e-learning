<script setup lang="ts">
import RichTextEditor from '@/components/RichTextEditor.vue'
import { prepareImageUpload } from '@/lib/image-upload'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const form = useForm({
  title: '',
  description: '',
  cover_image: null as File | null,
})

const coverPreviewUrl = computed(() => (form.cover_image ? URL.createObjectURL(form.cover_image) : null))

async function onCoverSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  form.cover_image = file ? await prepareImageUpload(file) : null
}

function submit() {
  form.post('/teacher/courses', { forceFormData: true })
}
</script>

<template>
  <Head title="Vytvoriť kurz" />

  <div class="min-h-screen bg-[#f7f4ee] text-slate-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_80%_at_10%_10%,rgba(251,191,36,0.18),transparent_60%),radial-gradient(70%_80%_at_90%_20%,rgba(56,189,248,0.12),transparent_60%)]"></div>

    <div class="max-w-4xl mx-auto px-6 py-10">
      <Link href="/dashboard" class="text-slate-500 hover:text-slate-800">← Späť do prehľadu</Link>

      <h1 class="mt-4 text-3xl font-display">Vytvoriť kurz</h1>
      <p class="mt-2 text-slate-600">Vyplňte základné informácie o kurze a pripravte úvodnú vizuálnu kartu.</p>

      <form @submit.prevent="submit" class="mt-6 space-y-6 rounded-3xl bg-white/85 ring-1 ring-black/5 p-6">
        <div>
          <label class="text-sm text-slate-600">Názov kurzu</label>
          <input
            v-model="form.title"
            type="text"
            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none focus:border-slate-400"
            placeholder="Napríklad: Základy HTML"
          />
          <div v-if="form.errors.title" class="text-rose-600 text-sm mt-2">
            {{ form.errors.title }}
          </div>
        </div>

        <div>
          <label class="text-sm text-slate-600">Titulný obrázok kurzu</label>
          <input
            type="file"
            accept="image/*"
            class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700"
            @change="onCoverSelected"
          />
          <div v-if="form.errors.cover_image" class="text-rose-600 text-sm mt-2">
            {{ form.errors.cover_image }}
          </div>

          <div
            v-if="coverPreviewUrl"
            class="mt-4 overflow-hidden rounded-[1.5rem] border border-slate-200 bg-[#fbfaf7]"
          >
            <img :src="coverPreviewUrl" alt="Preview" class="h-56 w-full object-cover" />
          </div>
        </div>

        <div>
          <label class="text-sm text-slate-600">Popis kurzu</label>
          <div class="mt-2">
            <RichTextEditor v-model="form.description" upload-url="/teacher/uploads/images" min-height-class="min-h-[220px]" />
          </div>
          <div v-if="form.errors.description" class="text-rose-600 text-sm mt-2">
            {{ form.errors.description }}
          </div>
        </div>

        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-white hover:bg-slate-800 disabled:opacity-50"
          :disabled="form.processing"
        >
          Vytvoriť kurz
        </button>
      </form>
    </div>
  </div>
</template>

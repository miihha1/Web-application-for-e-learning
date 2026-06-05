<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import TextAlign from '@tiptap/extension-text-align'
import { TextStyle } from '@tiptap/extension-text-style'
import Underline from '@tiptap/extension-underline'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { FontSize } from '@/lib/tiptap-font-size'
import { prepareImageUpload } from '@/lib/image-upload'

const props = withDefaults(defineProps<{
  modelValue: string
  uploadUrl?: string | null
  minHeightClass?: string
}>(), {
  uploadUrl: null,
  minHeightClass: 'min-h-[280px]',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const isUploading = ref(false)
const imageInput = ref<HTMLInputElement | null>(null)
const fontSizes = ['14px', '16px', '18px', '24px', '32px', '40px']

function getXsrfToken() {
  const cookie = document.cookie
    .split('; ')
    .find(entry => entry.startsWith('XSRF-TOKEN='))

  return cookie ? decodeURIComponent(cookie.split('=')[1]) : ''
}

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({
      heading: {
        levels: [1, 2, 3],
      },
    }),
    Underline,
    TextStyle,
    FontSize,
    Image.configure({
      inline: false,
      allowBase64: false,
    }),
    TextAlign.configure({
      types: ['heading', 'paragraph'],
    }),
  ],
  editorProps: {
    attributes: {
      class: `rich-editor__content ${props.minHeightClass} focus:outline-none`,
    },
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
})

watch(
  () => props.modelValue,
  value => {
    if (!editor.value) return

    if (value !== editor.value.getHTML()) {
      editor.value.commands.setContent(value || '', { emitUpdate: false })
    }
  },
)

const currentFontSize = computed(() => {
  const size = editor.value?.getAttributes('textStyle')?.fontSize
  return typeof size === 'string' && size ? size : '16px'
})

async function uploadImage(file: File) {
  if (!props.uploadUrl || !editor.value) return
  const preparedFile = await prepareImageUpload(file)

  const formData = new FormData()
  formData.append('image', preparedFile)

  isUploading.value = true

  try {
    const response = await fetch(props.uploadUrl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getXsrfToken(),
      },
    })

    if (!response.ok) {
      throw new Error('Image upload failed')
    }

    const payload = await response.json()
    if (payload.url) {
      editor.value.chain().focus().setImage({ src: payload.url }).run()
    }
  } finally {
    isUploading.value = false
  }
}

async function onImageSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (file) {
    await uploadImage(file)
  }

  input.value = ''
}

function setParagraph() {
  editor.value?.chain().focus().setParagraph().run()
}

function setHeading(level: 1 | 2 | 3) {
  editor.value?.chain().focus().toggleHeading({ level }).run()
}

function setFontSize(size: string) {
  editor.value?.chain().focus().setFontSize(size).run()
}

function onFontSizeChange(event: Event) {
  const target = event.target as HTMLSelectElement
  setFontSize(target.value)
}

onBeforeUnmount(() => {
  editor.value?.destroy()
})
</script>

<template>
  <div data-i18n-skip class="rich-editor rounded-[1.5rem] border border-slate-200 bg-white shadow-[0_25px_60px_-45px_rgba(15,23,42,0.45)]">
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-[#fbfaf7] px-4 py-3">
      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive('bold') }" @click="editor?.chain().focus().toggleBold().run()">B</button>
      <button type="button" class="rich-editor__button italic" :class="{ 'is-active': editor?.isActive('italic') }" @click="editor?.chain().focus().toggleItalic().run()">I</button>
      <button type="button" class="rich-editor__button underline" :class="{ 'is-active': editor?.isActive('underline') }" @click="editor?.chain().focus().toggleUnderline().run()">U</button>
      <button type="button" class="rich-editor__button line-through" :class="{ 'is-active': editor?.isActive('strike') }" @click="editor?.chain().focus().toggleStrike().run()">S</button>

      <select class="rich-editor__select" @change="onFontSizeChange" :value="currentFontSize">
        <option v-for="size in fontSizes" :key="size" :value="size">{{ size }}</option>
      </select>

      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive('heading', { level: 1 }) }" @click="setHeading(1)">H1</button>
      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive('heading', { level: 2 }) }" @click="setHeading(2)">H2</button>
      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive('heading', { level: 3 }) }" @click="setHeading(3)">H3</button>
      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive('paragraph') }" @click="setParagraph()">P</button>

      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive({ textAlign: 'left' }) }" @click="editor?.chain().focus().setTextAlign('left').run()">L</button>
      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive({ textAlign: 'center' }) }" @click="editor?.chain().focus().setTextAlign('center').run()">C</button>
      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive({ textAlign: 'right' }) }" @click="editor?.chain().focus().setTextAlign('right').run()">R</button>

      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive('bulletList') }" @click="editor?.chain().focus().toggleBulletList().run()">• List</button>
      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive('orderedList') }" @click="editor?.chain().focus().toggleOrderedList().run()">1. List</button>
      <button type="button" class="rich-editor__button" :class="{ 'is-active': editor?.isActive('blockquote') }" @click="editor?.chain().focus().toggleBlockquote().run()">Quote</button>

      <button type="button" class="rich-editor__button" :disabled="!uploadUrl || isUploading" @click="imageInput?.click()">
        {{ isUploading ? 'Uploading...' : 'Image' }}
      </button>
      <input ref="imageInput" type="file" accept="image/*" class="hidden" @change="onImageSelected" />
    </div>

    <EditorContent v-if="editor" :editor="editor" class="px-5 py-4" />
  </div>
</template>

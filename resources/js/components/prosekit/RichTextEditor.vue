<script setup>
import 'prosekit/basic/style.css'

import { ref, watchPostEffect } from 'vue'
import { createEditor } from 'prosekit/core'
import { defineExtension } from './extension.ts'
import { markdownFromHTML } from '@/markdown-utils'
import { ProseKit, useDocChange } from 'prosekit/vue'
import ProsekitToolbar from './ProsekitToolbar.vue'
import ProsekitInlineMenu from './ProsekitInlineMenu.vue'
import DOMPurify from 'dompurify'

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    markdownFormat: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue'])

const extension = defineExtension()

const initialContent = props.markdownFormat ? htmlFromMarkdown(props.modelValue || '') : DOMPurify.sanitize(props.modelValue)
const editor = createEditor({ extension, defaultContent: initialContent })

useDocChange((/* doc */) => {
    const newContent = props.markdownFormat ? markdownFromHTML(editor.getDocHTML()) : DOMPurify.sanitize(editor.getDocHTML())
    emit('update:modelValue', newContent)
}, { editor })

const editorRef = ref(null)

watchPostEffect((onCleanup) => {
    editor.mount(editorRef.value)
    onCleanup(() => editor.unmount())
})
</script>

<template>
  <ProseKit :editor="editor">
    <div
      class="box-border h-full w-full min-h-36 overflow-y-hidden overflow-x-hidden rounded-md border border-solid border-gray-200 shadow flex flex-col bg-white"
    >
      <ProsekitToolbar />
      <ProsekitInlineMenu />
      <div class="relative w-full flex-1 box-border overflow-y-scroll">
        <div
          ref="editorRef"
          class="ProseMirror box-border min-h-full px-4 py-4 outline-none outline-0 [&_span[data-mention=&quot;user&quot;]]:text-blue-500 [&_span[data-mention=&quot;tag&quot;]]:text-violet-500"
        />
      </div>
    </div>
  </ProseKit>
</template>

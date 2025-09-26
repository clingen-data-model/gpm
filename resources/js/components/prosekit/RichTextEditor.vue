<script setup>
import 'prosekit/basic/style.css'

import { ref, watch, watchPostEffect } from 'vue'
import { createEditor } from 'prosekit/core'
import { defineExtension } from './extension.ts'
import { htmlFromMarkdown, markdownFromHTML } from '@/markdown-utils'
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
        default: true,
    },
})

const emit = defineEmits(['update:modelValue'])

const extension = defineExtension()

const formatValue = (value) => {
    return props.markdownFormat ? htmlFromMarkdown(value) : DOMPurify.sanitize(value)
}
const initialContent = formatValue(props.modelValue)
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

watch(() => props.modelValue, (newValue) => {
    const incomingContent = formatValue(newValue)
    if (editor.getDocHTML() !== incomingContent) {
        editor.setContent(incomingContent)
    }
})

/*
watchPostEffect(() => {
    const newContent = formatValue(props.modelValue)
    if (editor.getDocHTML() !== newContent) {
        editor.setContent(newContent)
    }
})
*/
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

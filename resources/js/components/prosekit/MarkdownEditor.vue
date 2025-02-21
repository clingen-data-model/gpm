<script setup>
import 'prosekit/basic/style.css'

import { ref, watchPostEffect } from 'vue'
import { createEditor, jsonFromHTML, htmlFromNode } from 'prosekit/core'
import { defineExtension } from './extension.ts'
import { markdownFromHTML, htmlFromMarkdown } from '@/markdown-utils'
import { ProseKit, useDocChange } from 'prosekit/vue'
import ProsekitToolbar from './ProsekitToolbar.vue'
import { watchIgnorable } from '@vueuse/core'

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
})

const emit = defineEmits(['update:modelValue'])

const extension = defineExtension()
const editor = createEditor({ extension, defaultContent: htmlFromMarkdown(props.modelValue || '') })

useDocChange((doc) => {
    emit('update:modelValue', markdownFromHTML(editor.getDocHTML()))
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
            class='box-border h-full w-full min-h-36 overflow-y-hidden overflow-x-hidden rounded-md border border-solid border-gray-200 shadow flex flex-col bg-white'>
            <ProsekitToolbar />
            <div class='relative w-full flex-1 box-border overflow-y-scroll'>
                <div ref="editorRef"
                    class='ProseMirror box-border min-h-full px-4 py-4 outline-none outline-0 [&_span[data-mention="user"]]:text-blue-500 [&_span[data-mention="tag"]]:text-violet-500' />
            </div>
        </div>
    </ProseKit>
</template>

<script setup lang="ts">
import type { EditorExtension } from './extension'

import { useEditor } from 'prosekit/vue'
import Button from './ProsekitButton.vue'

const editor = useEditor<EditorExtension>({ update: true })
</script>

<template>
    <div
        class='z-2 box-border border-zinc-200 dark:border-zinc-800 border-solid border-l-0 border-r-0 border-t-0 border-b flex flex-wrap gap-1 p-2 items-center'>
        <Button :pressed="false" :disabled="!editor.commands.undo.canExec()" @click="() => editor.commands.undo()">
            <div class='i-lucide-undo-2 h-5 w-5' />
        </Button>

        <Button :pressed="false" :disabled="!editor.commands.redo.canExec()" @click="() => editor.commands.redo()">
            <div class='i-lucide-redo-2 h-5 w-5' />
        </Button>

        <Button :pressed="editor.marks.bold.isActive()" :disabled="!editor.commands.toggleBold.canExec()"
            @click="() => editor.commands.toggleBold()">
            <div class='i-lucide-bold h-5 w-5' />
        </Button>

        <Button :pressed="editor.marks.italic.isActive()" :disabled="!editor.commands.toggleItalic.canExec()"
            @click="() => editor.commands.toggleItalic()">
            <div class='i-lucide-italic h-5 w-5' />
        </Button>

        <Button :pressed="editor.marks.underline.isActive()" :disabled="!editor.commands.toggleUnderline.canExec()"
            @click="() => editor.commands.toggleUnderline()">
            <div class='i-lucide-underline h-5 w-5' />
        </Button>

        <Button :pressed="editor.nodes.horizontalRule.isActive()" :disabled="!editor.commands.insertHorizontalRule.canExec()"
            @click="() => editor.commands.insertHorizontalRule()">
            <div class='i-lucide-minus h-5 w-5' />
        </Button>

        <Button :pressed="editor.nodes.list.isActive({ kind: 'bullet' })"
            :disabled="!editor.commands.toggleList.canExec({ kind: 'bullet' })"
            @click="() => editor.commands.toggleList({ kind: 'bullet' })">
            <div class='i-lucide-list h-5 w-5' />
        </Button>

        <Button :pressed="editor.nodes.list.isActive({ kind: 'ordered' })"
            :disabled="!editor.commands.toggleList.canExec({ kind: 'ordered' })"
            @click="() => editor.commands.toggleList({ kind: 'ordered' })">
            <div class='i-lucide-list-ordered h-5 w-5' />
        </Button>

        <Button :pressed="editor.nodes.blockquote.isActive()" :disabled="!editor.commands.toggleBlockquote.canExec()"
            @click="() => editor.commands.toggleBlockquote()">
            <div class='i-lucide-quote h-5 w-5' />
        </Button>

        <Button :pressed="false"
            :disabled="!editor.commands.indentList.canExec()"
            @click="() => editor.commands.indentList()">
            <div class='i-lucide-indent-increase h-5 w-5' />
        </Button>

        <Button :pressed="false"
            :disabled="!editor.commands.dedentList.canExec()"
            @click="() => editor.commands.dedentList()">
            <div class='i-lucide-indent-decrease h-5 w-5' />
        </Button>

        <Button :pressed="editor.nodes.heading.isActive({ level: 1 })"
            :disabled="!editor.commands.toggleHeading.canExec({ level: 1 })"
            @click="() => editor.commands.toggleHeading({ level: 1 })">
            <div class='i-lucide-heading-1 h-5 w-5' />
        </Button>

        <Button :pressed="editor.nodes.heading.isActive({ level: 2 })"
            :disabled="!editor.commands.toggleHeading.canExec({ level: 2 })"
            @click="() => editor.commands.toggleHeading({ level: 2 })">
            <div class='i-lucide-heading-2 h-5 w-5' />
        </Button>

        <Button :pressed="editor.nodes.heading.isActive({ level: 3 })"
            :disabled="!editor.commands.toggleHeading.canExec({ level: 3 })"
            @click="() => editor.commands.toggleHeading({ level: 3 })">
            <div class='i-lucide-heading-3 h-5 w-5' />
        </Button>
    </div>
</template>
<script setup lang="ts">
import type { EditorState } from 'prosekit/pm/state'
import { useEditor } from 'prosekit/vue'
import { InlinePopover } from 'prosekit/vue/inline-popover'
import { ref } from 'vue'

import Button from './ProsekitButton.vue'
import type { EditorExtension } from './extension'

const editor = useEditor<EditorExtension>({ update: true })
const linkMenuOpen = ref(false)

function setLinkMenuOpen(value: boolean) {
    linkMenuOpen.value = value
}
function toggleLinkMenuOpen() {
    linkMenuOpen.value = !linkMenuOpen.value
}

function getCurrentLink(state: EditorState): string | undefined {
    const { $from } = state.selection
    const marks = $from.marksAcross($from)
    if (!marks) {
        return
    }
    for (const mark of marks) {
        if (mark.type.name === 'link') {
            return mark.attrs.href
        }
    }
}

function handleLinkUpdate(href?: string) {
    if (href) {
        editor.value.commands.addLink({ href })
    } else {
        editor.value.commands.removeLink()
    }

    linkMenuOpen.value = false
    editor.value.focus()
}
</script>

<template>
  <InlinePopover
    data-testid="inline-menu-main"
    class="z-10 box-border border border-zinc-200 bg-white shadow-lg [&:not([data-state])]:hidden relative flex min-w-[8rem] space-x-1 overflow-auto whitespace-nowrap rounded-md p-1"
  >
    <Button
      :pressed="editor.marks.bold.isActive()" :disabled="!editor.commands.toggleBold.canExec()" tooltip="Bold"
      @click="() => editor.commands.toggleBold()"
    >
      <div class="i-lucide-bold h-5 w-5" />
    </Button>

    <Button
      :pressed="editor.marks.italic.isActive()" :disabled="!editor.commands.toggleItalic.canExec()"
      tooltip="Italic" @click="() => editor.commands.toggleItalic()"
    >
      <div class="i-lucide-italic h-5 w-5" />
    </Button>

    <Button
      :pressed="editor.marks.underline.isActive()" :disabled="!editor.commands.toggleUnderline.canExec()"
      tooltip="Underline" @click="() => editor.commands.toggleUnderline()"
    >
      <div class="i-lucide-underline h-5 w-5" />
    </Button>

    <Button
      v-if="editor.commands.addLink.canExec({ href: '' })" :pressed="editor.marks.link.isActive()"
      tooltip="Link" @click="
        () => {
          editor.commands.expandLink()
          toggleLinkMenuOpen()
        }
      "
    >
      <div class="i-lucide-link h-5 w-5" />
    </Button>
  </InlinePopover>

  <InlinePopover
    placement="bottom" :default-open="false" :open="linkMenuOpen" data-testid="inline-menu-link"
    class="z-10 box-border border border-zinc-200 bg-white shadow-lg [&:not([data-state])]:hidden relative flex flex-col w-xs rounded-lg p-4 gap-y-2 items-stretch"
    @open-change="setLinkMenuOpen"
  >
    <form
      v-if="linkMenuOpen" @submit.prevent="
        (event) => {
          const target = event.target as HTMLFormElement | null
          const href = target?.querySelector('input')?.value?.trim()
          handleLinkUpdate(href)
        }
      "
    >
      <input
        placeholder="Paste the link..." :defaultValue="getCurrentLink(editor.state)"
        class="flex h-9 rounded-md w-full bg-white px-3 py-2 text-sm placeholder:text-zinc-500 transition border box-border border-zinc-200 border-solid ring-0 ring-transparent focus-visible:ring-2 focus-visible:ring-zinc-900 focus-visible:ring-offset-0 outline-none focus-visible:outline-none file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:cursor-not-allowed disabled:opacity-50"
      >
    </form>
    <button
      v-if="editor.marks.link.isActive()"
      class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border-0 bg-zinc-900 text-zinc-50 hover:bg-zinc-900/90 h-9 px-3"
      @click="handleLinkUpdate()" @mousedown.prevent
    >
      Remove link
    </button>
  </InlinePopover>
</template>
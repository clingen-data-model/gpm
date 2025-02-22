<script setup>

import { Crepe } from '@milkdown/crepe'
import { Milkdown, useEditor } from "@milkdown/vue";
import { getHTML } from '@milkdown/utils'
import { markdownFromHTML } from '@/markdown-utils'

const model = defineModel()

const props = defineProps({
    htmlFormat: {
        type: Boolean,
        default: false,
    },
    editable: {
        type: Boolean,
        default: true,
    },
})

useEditor((root) => {
    let defaultValue = model.value
    if (props.htmlFormat) {
        defaultValue = markdownFromHTML(defaultValue)
    }
    const crepe = new Crepe({
        root,
        defaultValue: defaultValue,
        features: {
            [Crepe.Feature.CodeMirror]: false,
            [Crepe.Feature.Latex]: false,
            [Crepe.Feature.ImageBlock]: false,
        }
    })
    crepe.on(listener => {
        listener.markdownUpdated((ctx, markdown, /* prevMarkdown */) => {
            if (props.htmlFormat) {
                model.value = getHTML()(ctx)
            } else {
                model.value = markdown
            }
        })
    })
    return crepe;
})
</script>

<template>
    <Milkdown />
</template>

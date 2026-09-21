<script setup>
import { computed } from 'vue'

const props = defineProps({ before: { type: String, default: '' }, after: { type: String, default: '' } })
const tokens = computed(() => {
  let a = (props.before ?? '').match(/\s+|[^\s]+/gu) ?? []
  let b = (props.after ?? '').match(/\s+|[^\s]+/gu) ?? []
  if (props.before === props.after) return [{ operation: 'same', text: props.before }]
  // Exclude unchanged context before bounding the LCS table. A small edit in a
  // long description should not trigger a replacement of the entire text.
  let start = 0
  let endA = a.length
  let endB = b.length
  while (start < endA && start < endB && a[start] === b[start]) start++
  while (endA > start && endB > start && a[endA - 1] === b[endB - 1]) endA--, endB--
  const prefix = a.slice(0, start).join('')
  const suffix = a.slice(endA).join('')
  a = a.slice(start, endA)
  b = b.slice(start, endB)
  const result = []
  const append = (operation, text) => {
    if (!text) return
    if (result.at(-1)?.operation === operation) result.at(-1).text += text
    else result.push({ operation, text })
  }
  append('same', prefix)
  // Retain exact text and unchanged context for unusually large changed regions.
  if (a.length * b.length > 250000) {
    append('removed', a.join(''))
    append('added', b.join(''))
    append('same', suffix)
    return result
  }
  const lengths = Array.from({ length: a.length + 1 }, () => new Uint32Array(b.length + 1))
  for (let i = a.length - 1; i >= 0; i--) {
    for (let j = b.length - 1; j >= 0; j--) {
      lengths[i][j] = a[i] === b[j] ? lengths[i + 1][j + 1] + 1 : Math.max(lengths[i + 1][j], lengths[i][j + 1])
    }
  }
  let i = 0
  let j = 0
  while (i < a.length || j < b.length) {
    if (i < a.length && j < b.length && a[i] === b[j]) append('same', a[i++]), j++
    else if (i < a.length && (j === b.length || lengths[i + 1][j] >= lengths[i][j + 1])) append('removed', a[i++])
    else append('added', b[j++])
  }
  append('same', suffix)
  return result
})
</script>

<template>
  <span class="whitespace-pre-wrap break-words"><template v-for="(token, index) in tokens" :key="index"><del v-if="token.operation === 'removed'" class="text-red-700 bg-red-50"><span class="sr-only">Removed: </span>{{ token.text }}</del><ins v-else-if="token.operation === 'added'" class="text-green-800 bg-green-50"><span class="sr-only">Added: </span>{{ token.text }}</ins><span v-else>{{ token.text }}</span></template></span>
</template>

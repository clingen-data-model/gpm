<script setup>
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue';
import MarkdownBlock from '@/components/MarkdownBlock.vue';

defineProps({ modelValue: { type: String, default: '' }, disabled: Boolean, label: { type: String, required: true } });
const emit = defineEmits(['update:modelValue']);
</script>
<template>
  <div :aria-label="label" class="space-y-2">
    <p class="font-medium">{{ label }}</p>
    <RichTextEditor v-if="!disabled" :model-value="modelValue || ''" @update:model-value="emit('update:modelValue', $event)" />
    <MarkdownBlock v-else :markdown="modelValue || ''" class="border rounded p-3 bg-gray-50" />
  </div>
</template>

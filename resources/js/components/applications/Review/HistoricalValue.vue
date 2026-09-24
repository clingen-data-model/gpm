<script setup>
defineProps({ value: { default: null } })
const label = key => key.replaceAll('_', ' ')
</script>

<template>
  <span v-if="value === null || value === undefined">Not recorded</span>
  <ul v-else-if="Array.isArray(value)" class="list-disc pl-5">
    <li v-for="(item, index) in value" :key="index"><HistoricalValue :value="item" /></li>
    <li v-if="!value.length">None</li>
  </ul>
  <dl v-else-if="typeof value === 'object'" class="ml-2">
    <template v-for="(item, key) in value" :key="key">
      <dt class="font-semibold capitalize">{{ label(key) }}</dt>
      <dd><HistoricalValue :value="item" /></dd>
    </template>
  </dl>
  <span v-else class="whitespace-pre-wrap">{{ value }}</span>
</template>

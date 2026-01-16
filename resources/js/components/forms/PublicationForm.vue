<script setup lang="ts">
import { ref } from 'vue';
import { watchDebounced } from '@vueuse/core';
import usePublicationLookup from "../../composables/usePublicationLookup";

const query = ref('');
const { citation, execute, pending, error } = usePublicationLookup(query);

watchDebounced(query, (newVal) => {
    console.log('query changed to', newVal);
    if (newVal && newVal.length > 4) {
        execute();
    }
}, { debounce: 2000 });

</script>

<template>
  <div>
    <input v-model="query" placeholder="Enter PubMed ID" />

    <div v-if="citation">
      <h2>Publication Details</h2>
      <pre>{{ citation }}</pre>
    </div>
    <div v-if="pending">Loading...</div>
    <div v-if="error">Error: {{ error.message }}</div>
    <pre>{{ pending }}</pre>
    <pre>{{ error }}</pre>
  </div>
</template>

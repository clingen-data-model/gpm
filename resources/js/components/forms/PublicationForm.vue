<script setup lang="ts">
import { ref } from 'vue';
import useRemotePublicationClient from '../../domain/remote_publication_client';

const publicationClient = useRemotePublicationClient();

const pmid = ref('');
const publicationData = ref(null as any);

const fetchPublicationData = async () => {
    if (pmid.value) {
        publicationData.value = await publicationClient.fetchFromUrl(pmid.value);
    }
};


</script>

<template>
  <div>
    <input v-model="pmid" placeholder="Enter PubMed ID" />
    <button @click="fetchPublicationData">Fetch Publication</button>

    <div v-if="publicationData">
      <h2>Publication Details</h2>
      <pre>{{ publicationData }}</pre>
    </div>
  </div>
</template>
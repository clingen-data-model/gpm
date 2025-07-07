<template>
  <div>
    <!-- Tabs -->
    <div class="flex space-x-2 border-b border-gray-300 mb-2">
      <button
        class="px-4 py-2"
        :class="activeTab === 'published' ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-600'"
        @click="activeTab = 'published'"
      >
        Published ({{ data.published?.length || 0 }})
      </button>
      <button
        class="px-4 py-2"
        :class="activeTab === 'notPublished' ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-600'"
        @click="activeTab = 'notPublished'"
      >
        In Progress ({{ data.notPublished?.length || 0 }})
      </button>
      <button
        class="px-4 py-2"
        :class="activeTab === 'notCurated' ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-600'"
        @click="activeTab = 'notCurated'"
      >
        Not Curated ({{ data.notCurated?.length || 0 }})
      </button>
    </div>

    <!-- Table: Published or In Progress -->
    <div class="mt-4" v-if="activeTab === 'published' || activeTab === 'notPublished'">
      <div class="overflow-x-auto mt-4">
        <table class="min-w-full table-auto border border-gray-300 rounded-md text-sm">
          <thead class="bg-gray-100 text-left">
            <tr>
              <th class="px-4 py-2 font-semibold text-gray-700">Gene</th>
              <th class="px-4 py-2 font-semibold text-gray-700">Disease</th>
              <th class="px-4 py-2 font-semibold text-gray-700">Expert Panel</th>
              <th class="px-4 py-2 font-semibold text-gray-700">Classification</th>
              <th class="px-4 py-2 font-semibold text-gray-700">Curation Type</th>
              <th class="px-4 py-2 font-semibold text-gray-700">Status</th>
              <th class="px-4 py-2 font-semibold text-gray-700">Status Date</th>
              <th class="px-4 py-2 font-semibold text-gray-700">Phenotype</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(item, index) in data[activeTab]"
              :key="index"
              class="hover:bg-gray-50 border-t"
            >
              <td class="px-4 py-2 font-mono font-medium text-blue-800">{{ item.gene_symbol }}</td>
              <td class="px-4 py-2 text-gray-700">{{ item.mondo_id || '' }} {{ item.disease || '' }}</td>
              <td class="px-4 py-2 text-gray-700">{{ item.expert_panel || '' }}</td>
              <td class="px-4 py-2 text-gray-700">{{ item.classification || '' }}</td>
              <td class="px-4 py-2 text-gray-700">{{ item.curation_type || '' }}</td>
              <td class="px-4 py-2 text-green-700 font-semibold">{{ item.current_status || '' }}</td>
              <td class="px-4 py-2 text-gray-600">{{ item.current_status_date }}</td>
              <td class="px-4 py-2 text-gray-700">{{ item.phenotype || '' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Not Curated -->
    <div v-if="activeTab === 'notCurated'">
      <div class="flex flex-wrap gap-2 mt-4">
        <span
          v-for="(item, index) in data.notCurated"
          :key="index"
          class="inline-block bg-gray-100 text-sm px-3 py-1 rounded font-mono"
        >
          {{ item.gene_symbol }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  data: {
    type: Object,
    required: true,
    default: () => ({
      published: [],
      notPublished: [],
      notCurated: [],
    }),
  },
})

const activeTab = ref('published')
</script>

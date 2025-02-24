<script>
import AnnualUpdateTable from "@/components/annual_updates/AnnualUpdateTable.vue";
import { api } from '@/http';

import { orderBy } from 'lodash-es';

export default {
  name: "AnnualUpdatesList",
  components: {
    AnnualUpdateTable,
  },
  data() {
    return {
      selectedWindow: null,
      windows: [],
      items: [],
      loading: false,
    };
  },
  computed: {
    sortedWindows() {
      return orderBy(this.windows, "for_year", "desc");
    },
    latestWindow() {
      return this.sortedWindows[0] || {};
    },
    gcepReviews() {
      return this.items.filter((i) => i.expert_panel.expert_panel_type_id === 1);
    },
    vcepReviews() {
      return this.items.filter((i) => i.expert_panel.expert_panel_type_id === 2);
    },
    selectedYear() {
        return this.selectedWindow ? this.selectedWindow.for_year : this.latestWindow.for_year
    },
    selectedStartDate() {
        return this.selectedWindow ? this.selectedWindow.start : this.latestWindow.start
    },
    selectedEndDate() {
        return this.selectedWindow ? this.selectedWindow.end : this.latestWindow.end
    }
  },
  watch: {
    selectedWindow (to) {
        if (to) {
            this.getItems()
        }
    }
  },
  mounted() {
    this.getWindows();
    this.getItems();
  },
  methods: {
    async getWindows() {
        this.windows = await api
            .get("/api/annual-updates/windows")
            .then((response) => response.data);
        this.selectedWindow = this.latestWindow;
    },

    async getItems() {
        let url = `/api/annual-updates`;
        if (this.selectedWindow) {
            url += `?window_id=${this.selectedWindow.id}`
        }
        this.loading = true;
        this.items = await api
            .get(url)
            .then((response) => response.data);
        this.loading = false;
    },
  },
};
</script>
<template>
  <div>
    <header class="flex justify-between items-center border-b mb-4">
      <h1 class="border-0 mb-0">
        Annual Updates for {{ selectedYear }}
      </h1>
      {{ formatDate(selectedStartDate) }} - {{ formatDate(selectedEndDate) }}
      <select
        v-if="windows.length > 1"
        v-model="selectedWindow"
        class="font-normal text-md"
      >
        <option
          v-for="window in sortedWindows"
          :key="window.id"
          :value="window"
        >
          {{ window.for_year }}
        </option>
      </select>
    </header>
    <tabs-container>
      <tab-item label="VCEPS">
        <div v-if="loading">Loading&hellip;</div>
        <AnnualUpdateTable v-else :items="vcepReviews" />
      </tab-item>
      <tab-item label="GCEPS">
        <div v-if="loading">Loading&hellip;</div>
        <AnnualUpdateTable v-else :items="gcepReviews" />
      </tab-item>
    </tabs-container>
  </div>
</template>
<style scoped>
    .radio-group > label {
    @apply border border-gray-400 px-2 py-1 mb-0;
    }
    .radio-group > label:first-child {
    @apply rounded-l-lg border-r-0;
    }
    .radio-group > label:last-child {
    @apply rounded-r-lg border-l-0;
    }
</style>

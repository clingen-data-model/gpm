<template>
    <div>
        <header class="flex justify-between items-center border-b mb-4">
            <h1 class="border-0 mb-0">Annual Reviews for {{latestWindow.for_year}}</h1>
            <select v-model="selectedYear" class="font-normal text-md" v-if="windows.length > 1">
                <option :value="window.for_year" v-for="window in sortedWindows" :key="window.id">
                    {{window.for_year}}
                </option>
            </select>
        </header>
        <tabs-container>
            <tab-item label="VCEPS">
                <div v-if="loading">Loading&hellip;</div>
                <annual-review-table v-else :items="vcepReviews" />
            </tab-item>
            <tab-item label="GCEPS">
                <div v-if="loading">Loading&hellip;</div>
                <annual-review-table v-else :items="gcepReviews" />
            </tab-item>
        </tabs-container>
    </div>
</template>
<style lang="postcss" scoped>
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
<script>
import {api} from '@/http'
import {orderBy} from 'lodash'

import AnnualReviewTable from '@/components/annual_reviews/AnnualReviewTable';

export default {
    name: 'AnnualReviewsList',
    components: {
        AnnualReviewTable
    },
    data() {
        return {
            selectedYear: null,
            windows: [],
            items: [],
            loading: false
        }
    },
    computed: {
        sortedWindows () {
            return orderBy(this.windows, 'for_year', 'desc')
        },
        latestWindow () {
            return this.sortedWindows[0] || {};
        },
        gcepReviews () {
            return this.items.filter(i => i.expert_panel.expert_panel_type_id == 1);
        },
        vcepReviews () {
            return this.items.filter(i => i.expert_panel.expert_panel_type_id == 2);
        }
    },
    methods: {
        async getWindows () {
            this.windows = await api.get('/api/annual-reviews/windows')
                            .then(response => response.data);
            this.selectedYear = this.latestWindow.for_year;
        },
        async getItems () {
            this.loading = true;
            this.items = await api.get('/api/annual-reviews')
                            .then(response => response.data);
            this.loading = false;
        },
    },
    mounted () {
        this.getWindows();
        this.getItems();
    }
}
</script>
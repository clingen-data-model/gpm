<script>
import EvidenceSummary from '@/components/expert_panels/EvidenceSummary.vue'
import EvidenceSummaryForm from '@/components/expert_panels/EvidenceSummaryForm.vue'

export default {
    name: 'EvidenceSummaryList',
    components: {
        EvidenceSummary,
        EvidenceSummaryForm
    },
    props: {
        readonly: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    emits: [
        'summariesAdded'
    ],
    data() {
        return {
            newSummaries: [],
            errors: {},
            summaries: [],
            showDeleteConfirm: false,
            selectedSummary: {gene: {}},
            loading: false
        }
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            },
            set (value) {
                this.$store.commit('groups/addItem', value)
            }
        },
        meetsRequirements() {
            return this.summaries.length > 4;
        },
        adding () {
            return this.newSummaries.length > 0;
        },
        canEdit () {
            return this.hasAnyPermission(['ep-applications-manage', ['application-edit', this.group]])
                && !this.readonly
        }
    },
    watch: {
        group: {
            immediate: true,
            handler () {
                if (this.group && this.group.id) {
                    this.getEvidenceSummaries();
                }
            }
        }
    },
    methods: {
        async getEvidenceSummaries () {
            this.loading = true;
            try {
                this.summaries = await this.$store.dispatch('groups/getEvidenceSummaries', this.group)
            } catch (error) {
                // eslint-disable-next-line no-console
                console.log(error);
            }
            this.loading = false;

        },
        startNewSummary() {
            if (this.readonly) {
                return;
            }
            this.newSummaries.push({
                gene: null,
                variant: null,
                summary: null
            });
        },
        clearNewSummaries() {
            this.newSummaries = [];
        },
        handleSavedSummary() {
            this.getEvidenceSummaries();
            this.clearNewSummaries();
            this.$emit('summariesAdded');
        },
        handleDeleted (summary) {
            const idx = this.summaries.findIndex(s => s.id === summary.id);
            if (idx > -1) {
                this.summaries.splice(idx, 1);
            }
            this.getEvidenceSummaries();
        },
        cancelAdd () {
            this.clearNewSummaries();
        },
        mergeSummary(summary) {
            const idx = this.summaries.findIndex(s => s.id === summary.id);
            if (idx > -1) {
                this.summaries.splice(idx, 1, summary);
                return;
            }

            this.summaries.push(summary);
        }
    },
}
</script>
<template>
    <div>
        <ul v-if="summaries.length > 0">
            <transition-group name="slide-fade-down">
                <li v-for="(summary, idx) in group.expert_panel.evidence_summaries" :key="idx" class="my-4 flex">
                    <div class="text-lg pr-4">{{ idx+1 }}</div>
                    <EvidenceSummary
                        :summary="summary"
                        :group="group"
                        class="flex-1"
                        :readonly="readonly"
                        @saved="handleSavedSummary"
                        @deleted="handleDeleted"
                    ></EvidenceSummary>
                </li>
            </transition-group>
        </ul>
        <div v-else-if="loading" class="well text-center">Loading...</div>
        <div v-else class="well text-center" :class="{'cursor-pointer': !readonly}" @click="startNewSummary">No example evidence summaries have been added.</div>
        <ul v-show="adding">
            <li v-for="(newSummary, idx) in newSummaries" :key="idx" class="my-4 flex">
                <div class="text-lg pr-4">{{ (idx+1+summaries.length) }}</div>
                <EvidenceSummaryForm
                    class="flex-1"
                    :group="group"
                    :summary="newSummary"
                    @saved="handleSavedSummary"
                    @canceled="cancelAdd"
                ></EvidenceSummaryForm>
            </li>
        </ul>
        <div v-show="!adding && canEdit">
            <button v-show="!adding" class="btn btn-xs" @click="startNewSummary">Add Summary</button>
        </div>
    </div>
</template>

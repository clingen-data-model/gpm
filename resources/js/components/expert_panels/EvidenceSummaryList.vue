<script>
import EvidenceSummary from '@/components/expert_panels/EvidenceSummary.vue'
import EvidenceSummaryForm from '@/components/expert_panels/EvidenceSummaryForm.vue'

export default {
    name: 'EvidenceSummaryList',
    components: {
        EvidenceSummary,
        EvidenceSummaryForm
    },
    emits: [
        'summariesAdded'
    ],
    props: {
        readonly: {
            type: Boolean,
            required: false,
            default: false
        }
    },
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
                <li class="my-4 flex" v-for="(summary, idx) in group.expert_panel.evidence_summaries" :key="idx">
                    <div class="text-lg pr-4">{{ idx+1 }}</div>
                    <EvidenceSummary
                        :summary="summary"
                        :group="group"
                        @saved="handleSavedSummary"
                        @deleted="handleDeleted"
                        class="flex-1"
                        :readonly="readonly"
                    ></EvidenceSummary>
                </li>
            </transition-group>
        </ul>
        <div class="well text-center" v-else-if="loading">Loading...</div>
        <div class="well text-center" :class="{'cursor-pointer': !readonly}" v-else @click="startNewSummary">No example evidence summaries have been added.</div>
        <ul v-show="adding">
            <li class="my-4 flex" v-for="(newSummary, idx) in newSummaries" :key="idx">
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
            <button class="btn btn-xs" @click="startNewSummary" v-show="!adding">Add Summary</button>
        </div>
    </div>
</template>

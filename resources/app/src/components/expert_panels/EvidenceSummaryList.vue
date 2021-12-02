<template>
    <div>
        <ul v-if="summaries.length > 0">
            <transition-group name="slide-fade-down">            
                <li class="my-4 flex" v-for="(summary, idx) in summaries" :key="idx">
                    <div class="text-lg pr-4">{{idx+1}}</div>
                    <evidence-summary 
                        :summary="summary" 
                        :group="group"
                        @saved="handleSavedSummary" 
                        @deleted="handleDeleted"
                        class="flex-1"
                    ></evidence-summary>
                </li>
            </transition-group>
        </ul>
        <div class="well text-center" v-else-if="loading">Loading...</div>
        <div class="well text-center cursor-pointer" v-else @click="startNewSummary">No example evidence summaries have been added.</div>
        <ul v-show="adding">
            <li class="my-4 flex" v-for="(newSummary, idx) in newSummaries" :key="idx">
                <div class="text-lg pr-4">{{(idx+1+summaries.length)}}</div>
                <evidence-summary-form 
                    class="flex-1" 
                    :group="group" 
                    :summary="newSummary" 
                    @saved="handleSavedSummary" 
                    @canceled="cancelAdd"
                ></evidence-summary-form>
            </li>
        </ul>
        <div v-show="!adding">
            <button class="btn btn-xs" @click="startNewSummary" v-show="!adding">Add Summary</button>
        </div>
    </div>
</template>
<script>
import api from '@/http/api'
import EvidenceSummary from '@/components/expert_panels/EvidenceSummary'
import EvidenceSummaryForm from '@/components/expert_panels/EvidenceSummaryForm'

export default {
    name: 'EvidenceSummaryList',
    components: {
        EvidenceSummary,
        EvidenceSummaryForm
    },
    emits: [
        'summaries-added'
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
    },
    watch: {
        group: {
            immediate: true,
            handler: function () {
                if (this.group && this.group.id) {
                    this.getEvidenceSummaries();
                }
            }
        }
    },
    methods: {
        addEvidenceSummary () {
        },
        async getEvidenceSummaries () {
            this.loading = true;
            try {
                this.summaries = await api.get(`/api/groups/${this.group.uuid}/application/evidence-summaries`)
                                    .then(response => response.data.data);
            } catch (error) {
                console.log(error);
            }
            this.loading = false;
            
        },
        startNewSummary() {
            this.newSummaries.push({
                gene: null,
                variant: null,
                summary: null
            });
        },
        clearNewSummaries() {
            this.newSummaries = [];
        },
        handleSavedSummary(newSummary) {
            this.mergeSummary(newSummary);
            this.clearNewSummaries();
            this.$emit('summaries-added');
        },
        handleDeleted (summary) {
            const idx = this.summaries.findIndex(s => s.id == summary.id);
            if (idx > -1) {
                this.summaries.splice(idx, 1);
            }
        },
        cancelAdd () {
            this.clearNewSummaries();
        },
        mergeSummary(summary) {
            const idx = this.summaries.findIndex(s => s.id == summary.id);
            if (idx > -1) {
                this.summaries.splice(idx, 1, summary);
                return;
            }

            this.summaries.push(summary);
        }
    },
}
</script>
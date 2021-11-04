<template>
    <div>
        <div class="flex space-x-2">
            <input-row class="mt-0 mb-0" :vertical="true" label="Gene">
                <select v-model="summaryClone.gene_id">
                    <option :value="null">Select...</option>
                    <option v-for="gene in groupGenes" 
                        :value="gene.id"
                        :key="gene.id"
                    >{{gene.gene.gene_symbol}}</option>
                </select>
            </input-row>
            <input-row class="mt-0 mb-0" v-model="summaryClone.variant" :vertical="true" label="Variant"></input-row>
        </div>
        <input-row class="mt-0 mb-0" label="Summary" :vertical="true" >
            <textarea rows="5" class="w-full" v-model="summaryClone.summary"></textarea>
        </input-row>
        <input-row class="mt-0" v-model="summaryClone.vci_url" label="VCI URL" :vertical="true" input-class="w-full"></input-row>
        <button-row submit-text="save" @submit="save" @cancel="cancel"></button-row>
    </div>
</template>
<script>
import api from '@/http/api'
import is_validation_error from '@/http/is_validation_error';


export default {
    name: 'EvidenceSummaryForm',
    props: {
        group: {
            required: true,
            type: Object,
        },
        summary: {
            required: true,
            type: Object
        }
    },
    emits: [
        'saved',
        'canceled'
    ],
    data() {
        return {
            groupGenes: [],
            summaryClone: {gene: {}}
        }
    },
    computed: {

    },
    watch: {
        group: {
            immediate: true,
            handler: function () {
                if (this.group && this.group.id) {
                    this.getGroupGenes();
                }
            }
        },
        summary: {
            immediate: true,
            handler: function () {
                this.summaryClone = {...this.summary}
            }
        }
    },
    methods: {
        async save () {
            try {
                let url = `/api/groups/${this.group.uuid}/application/evidence-summaries`;
                let method = 'post';
                if (this.summaryClone.id) {
                    url += `/${this.summaryClone.id}`
                    method = 'put';
                }
                const newSummary = await api({method, url, data: this.summaryClone})
                                            .then (response => response.data.data);

                this.$emit('saved', newSummary);
                this.editing = false;
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                }
            }
        },
        cancel () {
            this.initSummaryClone();
            this.$emit('canceled')
        },
        edit (summary) {
            summary.editing = true;
        },
        async getGroupGenes () {
            this.groupGenes = await api.get(`/api/groups/${this.group.uuid}/application/genes`)
                                .then(response => response.data);
        },
        initSummaryClone () {
            this.summaryClone = {gene: {}};
        }
    },
}
</script>
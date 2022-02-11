<style lang="postcss" scoped>
    table > thead > tr > th {
        @apply align-top;
    }
</style>
<template>
    <div>
        <table v-if="cois.length > 0">
            <thead>
                <tr>
                    <th style="min-width: 10rem; max-width: 10%">Name</th>
                    <th style="width: 22.5%">Do you work for a laboratory that offers fee-for-service testing related to the work of your Expert Panel?</th>
                    <th>
                        Have you made substantial contributions to the literature implicating a gene:disease relationship that relates to the work of your Expert Panel?
                    </th>
                    <th>
                        Do you have any other existing or planned independent efforts that will potentially overlap with the scope of your ClinGen work?
                    </th>
                    <th>
                        Do you have any other potential conflicts of interest to disclose (e.g. patents, intellectual property ownership, or paid consultancies related to any variants or genes associated with the work of your Expert Panel):
                    </th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="response in cois" :key="response.id">
                    <td>{{response.data.first_name}} {{response.data.last_name}}</td>
                    <td>{{resolveResponse(response.response_document.work_fee_lab)}}</td>
                    <td>
                        {{resolveResponse(response.response_document.contributions_to_gd_in_ep)}}
                        <span v-if="resolveResponse(response.response_document.contributions_to_genes)">
                            - Genes: {{response.response_document.contributions_to_genes.response}}
                        </span>
                    </td>
                    <td>
                        {{resolveResponse(response.response_document.independent_efforts)}}
                        <span v-if="response.response_document.independent_efforts.response > 0">
                            - {{response.response_document.independent_efforts_details.response}}
                        </span>
                    </td>
                    <td>
                        {{resolveResponse(response.response_document.coi)}}
                        <span v-if="response.response_document.coi.response > 0">
                            - {{response.response_document.coi_details.response}}
                        </span>
                    </td>
                    <td>
                        {{formatDate(response.completed_at)}}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import { api } from '../../http'
export default {
    name: 'CoiReport',
    props: {
        group: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            cois: [],
        }
    },
    computed: {
        firstResponse () {
            if (this.cois.length > 0) {
                return this.cois[0].resolveResponse;
            }
            return {}
        }
    },
    watch: {
        group: {
            immediate: true,
            handler () {
                this.getCoisForGroup();
            } 
        }
    },
    methods: {
        getCoisForGroup () {
            api.get(`/api/groups/${this.group.uuid}/expert-panel/cois`)
                .then(response => {
                    this.cois = response.data.map(coi => coi);
                });
        },
        resolveHeader (item) {
            if (item) {
                return item.question
            }
            return null;
        },
        resolveResponse (item) {
            if (item) {
                if (typeof item.response === 'number') {
                    switch (item.response) {
                        case 0:
                            return 'No'
                        case '2':
                            return 'Unsure'
                        default:
                            return 'Yes'
                    }
                }
                return item.response;
            }
        }
    }
}
</script>
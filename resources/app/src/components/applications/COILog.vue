<template>
    <div>
        <h4 class="text-md font-bold">Conflict of Interest</h4>
        <div>
            COI URL: 
            <router-link :to="application.coi_url" class="text-blue-500">
                https://{{$store.state.hostname}}{{application.coi_url}}
            </router-link>
        </div>
        <div v-if="!hasCois" class="px-3 py-2 rounded border border-gray-300 text-gray-500 bg-gray-200">
            No Conflict of interest surveys completed
        </div>
        <div v-if="hasCois">
            <data-table
                :fields="fields"
                :data="application.cois"
            >
                <template v-slot:cell-id="{item}">
                    <button class="btn btn-xs" @click="showResponse(item)">view</button> 
                </template>
            </data-table>
            <modal-dialog v-model="showResponseDialog" size="xl">
                <div v-if="currentResponse">
                    <h4 class="text-lg border-b pb-2 mb-4 font-bold">
                        COI response for 
                        {{currentResponse.first_name.response}} {{currentResponse.last_name.response}}
                    </h4>
                    <div class=" text-sm">
                    <dictionary-row label="Email" label-class="font-bold" class="pb-1 mb-1 border-b">{{currentResponse.email.response}}</dictionary-row>

                    <dictionary-row :label="currentResponse.work_fee_lab.question" :vertical="true"
                        class="pb-1 mb-1 border-b"
                        label-class="font-bold"
                     >
                        {{getQuestionValue(currentResponse.work_fee_lab.response)}}
                    </dictionary-row>

                    <dictionary-row :label="currentResponse.contributions_to_gd_in_ep.question" :vertical="true"
                        class="pb-1 mb-1 border-b"
                        label-class="font-bold"
                     >
                        {{getQuestionValue(currentResponse.contributions_to_gd_in_ep.response)}}
                        <dictionary-row :label="currentResponse.contributions_to_genes.question" :vertical="true"
                            v-if="currentResponse.contributions_to_gd_in_ep.response == 1"
                            class="pb-1 mb-1 ml-4"
                            label-class="font-bold"
                        >
                            {{getQuestionValue(currentResponse.contributions_to_genes.response)}}
                        </dictionary-row>
                    </dictionary-row>

                    <dictionary-row :label="currentResponse.independent_efforts.question" :vertical="true"
                        class="pb-1 mb-1 border-b"
                        label-class="font-bold"
                     >
                        {{getQuestionValue(currentResponse.independent_efforts.response)}}
                    </dictionary-row>

                    <dictionary-row :label="currentResponse.coi.question" :vertical="true"
                        class="pb-1 mb-1"
                        label-class="font-bold"
                     >
                        {{getQuestionValue(currentResponse.coi.response)}}
                    </dictionary-row>
                    </div>
                </div>
            </modal-dialog>
        </div>
    </div>

</template>
<script>
export default {
    props: {
        application: {
            required: true,
            type: Object
        }
    },
    data() {
        return {
            showResponseDialog: false,
            currentResponse: null,
            fields: [
                {
                    name: 'data.first_name',
                    label: 'Name',
                    type: String,
                    sortable: true,
                    resolveValue: function (item) {
                        return `${item.data.first_name} ${item.data.last_name}`
                    }
                },
                {
                    name: 'data.email',
                    label: 'Email',
                    type: String,
                    sortable: true,
                },
                {
                    name: 'created_at',
                    label: 'Date Completed',
                    type: Date,
                    sortable: true
                },
                {
                    name: 'id',
                    label: '',
                    sortale: false
                }
            ]
        }
    },
    computed: {
        hasCois() {
            return this.application.cois && this.application.cois.length > 0;
        },
    },
    methods: {
        showResponse(response) {
            this.currentResponse = response.response_document;
            this.showResponseDialog = true;
        },
        getQuestionValue(response) {
            if (response === 1) {
                return 'Yes';
            }
            if (response === 0) {
                return 'No';
            }

            return response;

        }

    }
}
</script>
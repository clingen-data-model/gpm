<template>
    <div v-if="response">
        <h4 class="text-lg border-b pb-2 mb-4 font-bold">
            COI response for 
            {{response.first_name.response}} {{response.last_name.response}}
        </h4>
        <div class="text-sm">
            <dictionary-row label="Email" label-class="font-bold" class="pb-1 mb-1 border-b">
                {{response.email.response}}
            </dictionary-row>
            
            <dictionary-row label="Name" label-class="font-bold" class="pb-1 mb-1 border-b">
                {{response.first_name.response}} {{response.last_name.response}}
            </dictionary-row>

            <dictionary-row label="COI File" v-if="response.document_uuid"  label-class="font-bold">
                <div class="flex-0">
                    <p class="mb-2">This is a legacy response.</p>
                    <button class="btn btn-xs" @click="downloadDocument(response.download_url.response)">
                        Download the COI.
                    </button>
                </div>
            </dictionary-row>
            <div v-if="!response.document_uuid">

                <dictionary-row :label="response.work_fee_lab.question" :vertical="true"
                    class="pb-1 mb-1 border-b"
                    label-class="font-bold"
                >
                    {{getQuestionValue(response.work_fee_lab.response)}}
                </dictionary-row>

                <dictionary-row :label="response.contributions_to_gd_in_ep.question" :vertical="true"
                    class="pb-1 mb-1 border-b"
                    label-class="font-bold"
                >
                    {{getQuestionValue(response.contributions_to_gd_in_ep.response)}}
                    <dictionary-row :label="response.contributions_to_genes.question" :vertical="true"
                        v-if="response.contributions_to_gd_in_ep.response == 1"
                        class="pb-1 mb-1 ml-4"
                        label-class="font-bold"
                    >
                        {{getQuestionValue(response.contributions_to_genes.response)}}
                    </dictionary-row>
                </dictionary-row>

                <dictionary-row :label="response.independent_efforts.question" :vertical="true"
                    class="pb-1 mb-1 border-b"
                    label-class="font-bold"
                >
                    {{getQuestionValue(response.independent_efforts.response)}}

                    <dictionary-row :label="response.independent_efforts_details.question" :vertical="true"
                        v-if="response.independent_efforts.response == 1"
                        class="pb-1 mb-1 ml-4"
                        label-class="font-bold"
                    >
                        {{getQuestionValue(response.independent_efforts_details.response)}}
                    </dictionary-row>
                
                </dictionary-row>

                <dictionary-row :label="response.coi.question" :vertical="true"
                    class="pb-1 mb-1"
                    label-class="font-bold"
                >
                    {{getQuestionValue(response.coi.response)}}

                    <dictionary-row :label="response.coi_details.question" :vertical="true"
                        v-if="response.coi.response == 1"
                        class="pb-1 mb-1 ml-4"
                        label-class="font-bold"
                    >
                        {{getQuestionValue(response.coi_details.response)}}
                    </dictionary-row>
                </dictionary-row>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: {
        response: {
            type: Object || null,
            required: true
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        isLegacy() {
            return false;
        }
    },
    methods: {
        getQuestionValue(response) {
            if (response === 1) {
                return 'Yes';
            }
            if (response === 0) {
                return 'No';
            }

            return response;
        },
        downloadDocument(downloadUrl) {
            window.location = downloadUrl;
        },
    }
}
</script>
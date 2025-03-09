<script>
export default {
    props: {
        coi: {
            type: Object,
            required: true
        },
        group: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {

        }
    },
    computed: {
        isLegacy() {
            return false;
        },
        response () {
            return this.coi.response_document
        },
        version () {
            return this.coi.version
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
            if (response === 2) {
                return 'Unsure';
            }

            return response;
        },
        downloadDocument(downloadUrl) {
            window.location = downloadUrl;
        },
    }
}
</script>
<template>
    <div v-if="response">
        <h2 class="block-title">
            COI response for
            <span v-if="coi.data.first_name">{{ titleCase(`${coi.data.first_name} ${coi.data.last_name}`) }} in </span>
            {{ group.name }}
            (v. {{ coi.version }})
        </h2>
        <div class="text-sm response-data">
            <dictionary-row label="Name" label-class="font-bold">
                {{ coi.data.first_name }} {{ coi.data.last_name }}
            </dictionary-row>

            <dictionary-row label="Email" label-class="font-bold">
                {{ coi.data.email }}
            </dictionary-row>

            <dictionary-row v-if="response.document_uuid" label="COI File" label-class="font-bold">
                <div class="flex-0">
                    <p class="mb-2">This is a legacy response.</p>
                    <button class="btn btn-xs" @click="downloadDocument(response.download_url.response)">
                        Download the COI.
                    </button>
                </div>
            </dictionary-row>
            <div v-if="!response.document_uuid" class="response-data">

                <dictionary-row :label="response.work_fee_lab.question" :vertical="true"

                    label-class="font-bold"
                >
                    {{ getQuestionValue(response.work_fee_lab.response) }}
                </dictionary-row>

                <dictionary-row
                    v-if="version == '1.0.0'"
                    :label="response.contributions_to_gd_in_ep.question" :vertical="true"
                    label-class="font-bold"
                >
                    {{ getQuestionValue(response.contributions_to_gd_in_ep.response) }}
                    <dictionary-row v-if="response.contributions_to_gd_in_ep.response == 1" :label="response.contributions_to_genes.question"
                        :vertical="true"
                        class="pb-1 mb-1 ml-4"
                        label-class="font-bold"
                    >
                        {{ getQuestionValue(response.contributions_to_genes.response) }}
                    </dictionary-row>
                </dictionary-row>

                <dictionary-row
                    v-if="version == '2.0.0'"
                    :label="response.contributions_to_gd_in_group.question" :vertical="true"
                    label-class="font-bold"
                >
                    {{ getQuestionValue(response.contributions_to_gd_in_group.response) }}
                    <dictionary-row v-if="response.contributions_to_gd_in_group.response == 1" :label="response.contributions_to_genes.question"
                        :vertical="true"
                        class="pb-1 mb-1 ml-4"
                        label-class="font-bold"
                    >
                        {{ getQuestionValue(response.contributions_to_genes.response) }}
                    </dictionary-row>
                </dictionary-row>

                <dictionary-row
                    v-if="version == '1.0.0'"
                    :label="response.independent_efforts.question"
                    :vertical="true"
                    label-class="font-bold"
                >
                    {{ getQuestionValue(response.independent_efforts.response) }}

                    <dictionary-row v-if="[1,2].includes(response.independent_efforts.response)" :label="response.independent_efforts_details.question"
                        :vertical="true"
                        class="pb-1 mb-1 ml-4 border-none"
                        label-class="font-bold"
                    >
                        {{ getQuestionValue(response.independent_efforts_details.response) }}
                    </dictionary-row>

                </dictionary-row>

                <dictionary-row :label="response.coi.question" :vertical="true"
                    class="pb-1 mb-1"
                    label-class="font-bold"
                >
                    {{ getQuestionValue(response.coi.response) }}

                    <dictionary-row v-if="[1,2].includes(response.coi.response)" :label="response.coi_details.question"
                        :vertical="true"
                        class="pb-1 mb-1 ml-4"
                        label-class="font-bold"
                    >
                        {{ getQuestionValue(response.coi_details.response) }}
                    </dictionary-row>
                </dictionary-row>
            </div>
        </div>
    </div>
</template>
<style lang="postcss">
    .response-data > .dictionary-row {
        @apply pb-2 mb-1 border-b border-gray-100;
    }
</style>

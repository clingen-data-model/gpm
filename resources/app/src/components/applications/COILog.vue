<template>
    <div>
        <div class="flex justify-between">
            <h4 class="text-md font-bold">Conflict of Interest</h4>
            <coi-legacy-upload :application="application"></coi-legacy-upload>
        </div>
        <div class="my-2 flex justify-between">
            <div>
                COI URL: 
                <router-link :to="application.coi_url" class="text-blue-500">
                    {{$store.state.hostname}}{{application.coi_url}}
                </router-link>
                &nbsp;
                <a :href="mailtoLink" class="btn btn-xs">Send url to contacts.</a>
            </div>
            <icon-refresh 
                :height="14" :width="14" 
                :class="{'animate-spin': refreshing}"
                @click="refresh"
            ></icon-refresh>
            <!-- add copy link button -->
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
                <coi-detail :response="currentResponse"></coi-detail>
            </modal-dialog>
        </div>
    </div>

</template>
<script>
import api from '../../http/api';
import IconRefresh from '../icons/IconRefresh';
import CoiDetail from './CoiDetail';
import CoiLegacyUpload from './CoiLegacyUpload';

export default {
    components: {
        IconRefresh,
        CoiDetail,
        CoiLegacyUpload,
    },
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
            ],
            refreshing: false
        }
    },
    computed: {
        hasCois() {
            return this.application.cois && this.application.cois.length > 0;
        },
        mailtoLink() {
            return `mailto:${this.application.contacts.map(p => p.email).join(';')}?subject=Your COI Link for your Expert Panel Application&body=Your expert panel's COI form: ${this.$store.state.hostname}${this.application.coi_url}.`
        }
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
        },
        async refresh() {
            this.refreshing = true;
            await this.$store.dispatch('applications/getApplication', this.application.uuid);
            this.refreshing = false;
        }
    }
}
</script>
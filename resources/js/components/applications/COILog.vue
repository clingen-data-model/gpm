<script>

import CoiDetail from '@/components/applications/CoiDetail.vue';
import CoiLegacyUpload from '@/components/applications/CoiLegacyUpload.vue';

export default {
    components: {
        CoiDetail,
        CoiLegacyUpload,
    },
    props: {
        group: {
            required: true,
            type: Object
        }
    },
    data() {
        return {
            showResponseDialog: false,
            currentCoi: null,
            fields: [
                {
                    name: 'data.first_name',
                    label: 'Name',
                    type: String,
                    sortable: true,
                    resolveValue (item) {
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
        application() {
            return this.group.expert_panel;
        },
        hasCois() {
            return this.application.cois && this.application.cois.length > 0;
        },
        mailtoLink() {
            return `mailto:${this.group.contacts.map(p => p.email).join(';')}?subject=Your COI Link for your Expert Panel Application&body=Your expert panel's COI form: ${this.$store.state.hostname}${this.application.coi_url}.`
        }
    },
    methods: {
        showResponse(coi) {
            this.currentCoi = coi;
            this.showResponseDialog = true;
        },
        async refresh() {
            this.refreshing = true;
            await this.$store.dispatch('applications/getApplication', this.application.uuid);
            this.refreshing = false;
        },
        mailToContacts() {
            this.$refs.mailtobutton.click()
        },
        getReport() {
            const reportUrl = `/api/report/${this.application.coi_code}`
            window.location = reportUrl;
        },

    }
}
</script>
<template>
  <div>
    <div class="flex justify-between">
      <h2>Conflict of Interest</h2>
      <CoiLegacyUpload
        v-if="$store.state.features.legacyCoi"
        :application="application"
      />
    </div>
    <div class="my-2 flex justify-between">
      <icon-refresh 
        :height="14" :width="14" 
        :class="{'animate-spin': refreshing}"
        @click="refresh"
      />
    </div>
    <div v-if="!hasCois" class="px-3 py-2 rounded border border-gray-300 text-gray-500 bg-gray-200">
      No Conflict of interest surveys completed
    </div>
    <div v-if="hasCois">
      <data-table
        :fields="fields"
        :data="application.cois"
      >
        <template #cell-id="{item}">
          <button class="btn btn-xs" @click="showResponse(item)">
            view
          </button> 
        </template>
      </data-table>
      <modal-dialog v-model="showResponseDialog" size="xl">
        <CoiDetail v-if="currentCoi" :coi="currentCoi" />
      </modal-dialog>
    </div>
  </div>
</template>
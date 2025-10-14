<script>
import { mapGetters } from 'vuex'
import { formatDate } from '@/date_utils'

const EP_TYPES = { GCEP: 1, VCEP: 2, SC_VCEP: 3 }

export default {
    name: 'CreateApplicationForm',
    props: {

    },
    emits: [
        'canceled',
        'saved',
    ],
    data() {
        return {
            visible: false,
            showInitiationDate: false,
            app: {
                working_name: null,
                cdwg_id: null,
                expert_panel_type_id: null,
                date_initiated: formatDate(new Date())
            },
            epTypes: [
                {name: 'GCEP', id: EP_TYPES.GCEP },
                {name: 'VCEP', id: EP_TYPES.VCEP },
                {name: 'SC-VCEP', id: EP_TYPES.SC_VCEP }
            ],
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            cdwgs: 'cdwgs/all',
            scCdwgs: 'sccdwgs/all'
        }),
        isScVcep() {
          return this.app.expert_panel_type_id === EP_TYPES.SC_VCEP
        },
        displayedCdwgs() {
          return this.isScVcep ? this.scCdwgs : this.cdwgs
        },
        hasErrors() {
            return Object.keys(this.errors).length > 0;
        }
    },
    watch: {
        'app.working_name': function () {
            this.clearErrors('working_name')
        },
        'app.cdwg_id': function () {
            this.clearErrors('cdwg_id')
        },
        'app.expert_panel_type_id': {
          async handler(newVal) {
            this.clearErrors('expert_panel_type_id')
            this.app.cdwg_id = null
          }
        }
    },
    methods: {
        cancel() {
            this.initForm();
            this.$emit('canceled');
        },
        async save() {
            try {
                await this.$store.dispatch('applications/initiateApplication', this.app)
                this.$emit('saved', this.app);
            } catch (error) {
                if (error.response && Number.parseInt(error.response.status) === 422 && error.response.data.errors) {
                    this.errors = error.response.data.errors
                    return;
                }
                throw(error)
            }
        },
        initForm() {
            this.initErrors();
            this.initAppData();
        },
        initAppData() {
            this.app = {
                working_name: null,
                cdwg_id: null,
                expert_panel_type_id: null,
                date_initiated: formatDate(new Date()),
                website_url: null,
            };
        },
        clearErrors(fieldName) {
            if (fieldName) {
                delete this.errors[fieldName];
                return;
            }

            this.initErrors();
        },
        initErrors() {
            this.errors = {}
        }
    }
}
</script>
<template>
  <form-container @keydown.enter="save">
    <h2 class="pb-2 border-b mb-4">
      Initiate Application
    </h2>
    <input-row
      v-model="app.working_name"
      label="Working Name"
      :errors="errors.working_name"
      type="text"
      placeholder="A recognizable name"
    />

    <input-row label="EP Type" :errors="errors.expert_panel_type_id">
      <div>
        <label
          v-for="epType in epTypes"
          :key="epType.id"
          :for="`ep-${epType.id}-radio`"
        >
          <input
            :id="`ep-${epType.id}-radio`"
            v-model="app.expert_panel_type_id"
            type="radio"
            :value="epType.id"
          >
          <div>{{ epType.name }}</div>
        </label>
      </div>
    </input-row>
    
    <input-row :label="isScVcep ? 'SC-CDWG' : 'CDWG'" :errors="errors.cdwg_id">
      <select v-model="app.cdwg_id" :key="'cdwg-'+String(app.expert_panel_type_id)">
        <option :value="null">
          Select...
        </option>
        <option v-for="cdwg in displayedCdwgs" :key="cdwg.id" :value="cdwg.id">
          {{ cdwg.name }}
        </option>
      </select>
    </input-row>

    <input-row :errors="errors.date_initiated">
      <div>
        <div>
          <checkbox
            id="show-initiation-checkbox"
            v-model="showInitiationDate"
            label="Backdate this initiation"
          />
        </div>
        <input-row
          v-show="showInitiationDate"
          v-model="app.date_initiated"
          type="date"
          label="Initiation Date"
        />
      </div>
    </input-row>

    <button-row>
      <button class="btn" @click="cancel">
        Cancel
      </button>
      <button class="btn blue" @click="save">
        Initiate Application
      </button>
    </button-row>
  </form-container>
</template>

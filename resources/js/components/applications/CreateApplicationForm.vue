<script>
import { formatDate } from '@/date_utils'
import { api } from '@/http'

export default {
  name: 'CreateApplicationForm',
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
      // Expert Panel Type ID, not from Group Type.
      epTypes: [
        {name: 'GCEP', id: 1},
        {name: 'VCEP', id: 2},
        {name: 'SC-VCEP', id: 3 },
      ],
      parents: [],
      parentsKey: null,
      errors: {}
    }
  },
  computed: {
    hasErrors() {
      return Object.keys(this.errors).length > 0
    },

    isScvcep() {
      return Number(this.app.expert_panel_type_id) === 3
    },

    parentLabel() {
      return this.isScvcep ? 'SC-CDWG' : 'CDWG'
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
      immediate: true,
      async handler (newVal, oldVal) {
        this.clearErrors('expert_panel_type_id')

        const newKey = this.getParentsKey(newVal)
        const oldKey = this.getParentsKey(oldVal)

        if (oldVal != null && newKey !== oldKey) {
          this.app.cdwg_id = null
        }

        await this.fetchParentsForEpType(newVal)
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
        date_initiated: formatDate(new Date())
      };
      this.parents = []
      this.parentsKey = null
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
    },

    // Parents loading logic, fetch it from backend
    getParentsKey(epTypeId) {
      const t = Number(epTypeId)
      if (t === 3) return 'cdwgs:sc'
      if ([1, 2].includes(t)) return 'cdwgs:all'
      return null
    },

    async fetchParentsForEpType(epTypeId) {
      const key = this.getParentsKey(epTypeId)

      if (!key) {
        this.parents = []
        this.parentsKey = null
        return
      }

      if (this.parentsKey === key && this.parents.length) return

      const params = {}
      if (key === 'cdwgs:sc') params.scope = 'sc'

      const rows = await api.get('/api/cdwgs', { params })
        .then(res => res.data?.data ?? res.data ?? [])

      this.parents = Array.isArray(rows) ? rows : []
      this.parentsKey = key
    }
  }
}
</script>

<template>
  <form-container @keydown.enter="save">
    <h2 class="pb-2 border-b mb-4">
      Initiate Application
    </h2>

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
    
    <input-row
      v-model="app.working_name"
      label="Working Name"
      :errors="errors.working_name"
      type="text"
      placeholder="A recognizable name"
    />

    <input-row :label="parentLabel" :errors="errors.cdwg_id">
      <select v-model="app.cdwg_id">
        <option :value="null">
          Select...
        </option>
        <option v-for="p in parents" :key="p.id" :value="p.id">
          {{ p.name }}
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

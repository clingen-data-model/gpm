<script>
// @ is an alias to /src
import CreateApplicationForm from '@/components/applications/CreateApplicationForm.vue'

export default {
  name: 'ApplicationsIndex',
  components: {
    CreateApplicationForm
  },
  data() {
    return {
      showModal: false
    }
  },
  computed: {
  },
  methods: {
    handleApplicationInitiated(applicationData) {
      this.showModal = false;
      this.$router.push({name: 'AddMemberToApplication', params: {uuid: applicationData.uuid}})
    }
  }
}
</script>

<template>
  <div class="home">
    <button class="btn blue" @click="showModal = true">
      Initiate Application
    </button>

    <div class="mb-2 mt-4">
      <div class="tabs">
        <router-link :to="{name: 'vceps'}" class="tab">
          VCEPS
        </router-link>
        <router-link :to="{name: 'gceps'}" class="tab">
          GCEPS
        </router-link>
      </div>
      <div class="p-4 border rounded-tr-lg rounded-b-lg bg-white">
        <router-view />
      </div>
    </div>


    <modal-dialog v-model="showModal" size="md" @closed="$refs.initiateform.initForm()">
      <CreateApplicationForm
        ref="initiateform" 
        name="modal" 
        @canceled="showModal = false" 
        @saved="handleApplicationInitiated"
      />
    </modal-dialog>
  </div>
</template>

<style lang="postcss" scoped>
.tabs {
    @apply flex space-x-2;
}

.tabs .tab {
    @apply border border-b-0 px-4 py-1 rounded-t-lg bg-gray-200 -mb-px;
}

.tabs .tab.router-link-active,
.tabs .tab.active {
    @apply bg-white no-underline;
}

</style>
<template>
  <div class="home">

    <button @click="showModal = true" class="btn blue">Initiate Application</button>

    <div class="mb-2 mt-4">
      <div class="tabs">
        <router-link to="/vceps" class="tab">VCEPS</router-link>
        <router-link to="/gceps" class="tab">GCEPS</router-link>
      </div>
      <div class="p-4 border rounded-tr-lg rounded-b-lg bg-white">
        <router-view></router-view>
      </div>
    </div>


      <modal-dialog v-model="showModal" size='md' @closed="$refs.initiateform.initForm()">
        <create-application-form name="modal" @canceled="showModal = false" @saved="showModal = false" ref="initiateform"></create-application-form>
      </modal-dialog>
  </div>
</template>

<script>
// @ is an alias to /src
import CreateApplicationForm from '../components/applications/CreateApplicationForm'

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
  watch: {
    'this.$route.hash': function (to, from) {
      console.log('to, from', [to, from]);
    }
  },
  computed: {
  }
}
</script>

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
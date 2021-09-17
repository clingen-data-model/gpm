<template>
    <div class="fixed right-4 top-8 w-64 z-50">
      <ul>
        <transition-group name="slide-fade" tag="li">
          <div v-for="alert in $store.state.alerts.alerts" :key="alert.uuid"
            class="flex justify-between rounded px-3 py-2 my-2 alert-item shadow-lg" 
            :class="`alert-${alert.type}`"
          >
            {{alert.message}}
            <button @click="$store.commit('removeAlert', alert.uuid)" class="hover:underline">x</button>
          </div>
        </transition-group>
      </ul>
    </div>

</template>
<script>
export default {
    props: {
        
    },
    data() {
        return {
            
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>
<style lang="postcss">
  .alert-info {
      @apply bg-blue-200 text-blue-800 border-blue-300 border;
  }
  .alert-success {
      @apply bg-green-200 text-green-800 border-green-300 border;
  }
  .alert-warning {
      @apply bg-yellow-200 text-yellow-800 border-yellow-300 border;
  }
  .alert-error {
      @apply bg-red-200 text-red-800 border-red-300 border;
  }
</style>
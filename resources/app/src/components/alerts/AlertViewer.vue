<template>
    <div class="fixed right-4 bottom-4 w-64">
      <ul>
        <transition-group name="slide-fade" tag="li">
          <div v-for="alert in $store.state.alerts.alerts" :key="alert.uuid"
            class="flex justify-between rounded px-3 py-2 my-2 alert-item" 
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
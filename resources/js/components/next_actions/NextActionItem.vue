<script>
export default {
    name: 'NextActionItem',
    props: {
        nextAction: {
            require: true,
            type: Object
        }
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
<template>
  <div class="md:flex">
    <div class="flex" v-html="nextAction.entry" />
    <div>
      <span v-if="nextAction.assigned_to_name">{{ nextAction.assigned_to_name }} from the </span>
      {{ nextAction.assigned_to }}
    </div>
    <button 
      class="btn btn-xs block flex-initial self-center" 
    >
      <!-- @click="showModal = true" -->
      Mark completed
    </button>
  </div>
</template>
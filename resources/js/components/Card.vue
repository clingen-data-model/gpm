<script>
export default {
    props: {
        title: {
            type: String,
            required: false
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
  <div>
    <slot name="header">
      <div v-if="title || $slots.title" class="bg-gray-200 px-4 py-2 sm:flex justify-between border border-gray-300 rounded-t">
        <slot name="title">
          <h2>{{ title }}</h2>
        </slot>
        <slot name="header-right" />
      </div>
    </slot>
    <div class="bg-white border border-t-0 border-gray-300 rounded-b  px-4 py-3 overflow-x-auto">
      <slot />
    </div>
  </div>
</template>
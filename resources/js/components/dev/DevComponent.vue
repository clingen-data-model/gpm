<script>
export default {
    name: 'DevComponent',
    props: {

    },
    data() {
        return {

        }
    },
    computed: {
        showDev () {
            return true
        }

    },
    methods: {
    }
}
</script>
<template>
  <div
    v-if="showDev && hasRole('super-user')"
    class="bg-pink-100 text-pink-700 border border-pink-700 px-4 py-2"
  >
    <slot />
  </div>
</template>

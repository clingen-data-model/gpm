<script>

export default {
    props: {
        size: {
            required: false,
            default: 'md',
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        dimension () {
            if (this.size === 'xs') {
                return 8
            }
            if (this.size === 'sm') {
                return 10
            }
            if (this.size === 'md') {
                return 12
            }
            if (this.size === 'lg') {
                return 14
            }
            if (this.size === 'xl') {
                return 18
            }
            return 10
        }
    },
    methods: {

    }
}
</script>
<template>
  <button class="btn-delete" @click="confirmRemove">
    <icon-close :height="dimension" :width="dimension" />
  </button>
</template>
<style lang="postcss">
    .btn-delete {
       @apply cursor-pointer inline-block p-0.5 rounded-full border border-red-500 text-red-500 align-text-top outline-none;
    }
    .btn-delete:active, .btn-delete:focus {
        @apply outline-none;
    }
    .btn-delete:active{
        @apply border-red-900 text-red-900 bg-gray-100 outline-none;
        transform: scale(.95)
    }
</style>
<script>
export default {
    name: 'IconLock',
    props: {
        iconName: {
            required: false,
            default: 'lock'
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
  <icon-base icon-name="iconName">
    <path d="M4 8V6a6 6 0 1 1 12 0v2h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-8c0-1.1.9-2 2-2h1zm5 6.73V17h2v-2.27a2 2 0 1 0-2 0zM7 6v2h6V6a3 3 0 0 0-6 0z" />
  </icon-base>
</template>
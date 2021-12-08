<template>
    <div class="application-step" id="pilot-specifications">
        <div class="header">
            <h2>{{title}}</h2>
        </div>
        <div class="step-contents">
            <slot></slot>
        </div>
    </div>
</template>
<script>
export default {
    name: 'ApplicationStep',
    props: {
        title: {
            type: String,
            required: false,
            default: null
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
<style lang="postcss" scoped>
    .application-step {
        @apply my-8 bg-white shadow-lg;
    }
    .application-step:first-child {
        @apply mt-4 border-t-0;
    }

    .application-step > .header{
        @apply border border-gray-300 border-b-0 py-2 px-4 bg-gray-200 rounded-t
    }

    .application-step > .step-contents {
        @apply pb-4 border border-gray-300;
    }
</style>
<template>
    <div class="application-step" id="pilot-specifications">
        <div class="header" v-if="title">
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
        @apply py-2 px-4;
        @apply bg-gray-50 border-t border-b;
        /* @apply border border-gray-300 border-b-0 */
    }

    .application-step > .step-contents {
        @apply pb-4;
    }
</style>
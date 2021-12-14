<template>
    <div class="application-step" :id="id">
        <div class="header" v-if="title">
            <h2 :class="{'text-gray-400': disabled}">
                {{title}}
                <icon-lock v-if="disabled" class="inline"></icon-lock>
            </h2>
        </div>
        <div class="step-contents relative">
            <slot></slot>
            <div class="z-20 absolute top-0 bottom-0 left-0 right-0 bg-white bg-opacity-50" v-if="disabled" />
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
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false
        },
        id: {
            type: String,
            required: true
        }
    },
    mounted() {
    },
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
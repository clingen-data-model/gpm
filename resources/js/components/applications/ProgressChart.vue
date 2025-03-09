<script>
import ProgressStep from '@/components/applications/ProgressStep.vue'

export default {
    components: {
        ProgressStep
    },
    props: {
        application: {
            required: true,
            type: Object
        }
    },
    computed: {
        steps () {
            return this.application.steps
        },
    }
}
</script>
<template>
    <div>
        <h3 class="pb-2">EP Progress</h3>
        <div class="flex flex-row space-x-1">
            <ProgressStep v-for="i in steps" :key="i" :step="i" :application="application"></ProgressStep>
        </div>
    </div>
</template>
<style lang="postcss" scoped>
    .progress-date {
        @apply text-xs;
        width: 10em;
    }
</style>
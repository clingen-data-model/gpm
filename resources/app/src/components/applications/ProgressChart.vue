<style lang="postcss" scoped>
    .progress-date {
        @apply text-xs;
        width: 10em;
    }
</style>
<template>
    <div>
        <h3 class="pb-2">EP Progress</h3>
        <div class="flex flex-row space-x-1">
            <progress-step v-for="i in steps" :key="i" :step="i" :application="application"></progress-step>
        </div>
    </div>
</template>
<script>
import ProgressStep from '@/components/applications/ProgressStep'

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
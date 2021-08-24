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
            if (this.application.expert_panel_type_id == 2) {
                return [1,2,3,4]
            }

            return [1]
        },
    }
}
</script>
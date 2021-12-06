<script>
import RequirementsBadge from '@/components/expert_panels/RequirementsBadge'

export default {
    name: 'ApplicationSection',
    components: {
        RequirementsBadge
    },
    props: {
        title: {
            type: String,
            required: false
        },
        name: {
            type: String,
            required: false
        }
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            }
        }
        
    }
}
</script>
<template>
    <div class="application-section">
        <header class="mb-2 flex justify-between">
            <slot name="title">
                <h2>{{title}}</h2>
            </slot>
            <requirements-badge v-if="name" :section="name" :group="group"></requirements-badge>
        </header>
        <div>
            <slot></slot>
        </div>
    </div>
</template>

<style lang="postcss" scoped>
    .application-section {
        @apply my-4 border border-gray-300 bg-white p-4
    }
    .application-section:first-child {
        @apply mt-0;
    }
</style>
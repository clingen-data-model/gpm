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
        id: {
            type: String,
            required: false
        }
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            }
        },
        name () {
            return this.id;
        }
    },
}
</script>
<template>
    <div :id="id" class="section-wrapper">
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
    </div>
</template>

<style lang="postcss" scoped>
    .section-wrapper {
        @apply border-b border-gray-200 bg-white py-4 px-6
    }
    .section-wrapper:last-child {
        @apply border-b-0
    }

    /* .section-wrapper:first-child {
        @apply pt-0;
    } */
</style>
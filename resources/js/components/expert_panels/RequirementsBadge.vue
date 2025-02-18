<template>
    <div v-if="hasRequirements">
        <popover arrow hover>
            <template v-slot:content>
                <div>
                    <requirements-item  v-for="(req, idx) in evaledRequirements" :key="idx" :requirement="req" />
                </div>
            </template>
            <badge :color="badgeColor" class="cursor-pointer">
                {{badgeText}}
            </badge>
        </popover>
    </div>
</template>
<script>
import RequirementsItem from './RequirementsItem.vue'

export default {
    name: 'RequirementsBadge',
    components: {
        RequirementsItem
    },
    props: {
        section: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
        }
    },
    computed: {
        group() {
            return this.$store.getters['groups/currentItemOrNew']
        },
        meetsRequirements () {
            return this.section.meetsRequirements(this.group);
        },
        badgeColor () {
            if (this.meetsRequirements) {
                return 'green';
            }
            return 'yellow'
        },
        badgeText () {
            const metCount = this.section.metRequirements(this.group).length;
            return `Requirements: ${metCount} / ${this.section.requirements.length}`;
        },
        evaledRequirements() {
            return this.section.evaluateRequirements(this.group);
        },
        hasRequirements () {
            return this.section.hasRequirements;
        }
    },
    methods: {

    }
}
</script>
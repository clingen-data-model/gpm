<template>
    <div v-if="hasRequirements">
        <popper arrow hover>
            <template v-slot:content>
                <requirements-item  v-for="(req, idx) in evaledRequirements" :key="idx" :requirement="req" />
            </template>
            <badge :color="badgeColor" class="cursor-pointer">
                {{badgeText}}
            </badge>
        </popper>
    </div>
</template>
<script>
import RequirementsItem from './RequirementsItem'

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

<style>
  :root {
    --popper-theme-background-color: #ffffff;
    --popper-theme-background-color-hover: #fff;
    --popper-theme-text-color: #333;
    --popper-theme-border-width: 0px;
    --popper-theme-border-style: solid;
    --popper-theme-border-radius: 6px;
    --popper-theme-padding: 1rem;
    --popper-theme-box-shadow: 0 6px 30px -6px rgba(0, 0, 0, 0.5);
  }
</style>
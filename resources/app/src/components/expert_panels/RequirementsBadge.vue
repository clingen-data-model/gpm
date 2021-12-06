<template>
    <div>
        <badge :color="badgeColor">
            {{badgeText}}
        </badge>
    </div>
</template>
<script>
import {VcepRequirements, GcepRequirements} from '@/domain/index'
import configs from '@/configs'

export default {
    name: 'RequirementsBadge',
    props: {
        section: {
            type: String,
            required: true
        }
    },
    data () {
        return {
            requirements: null,
        }
    },
    computed: {
        group() {
            return this.$store.getters['groups/currentItemOrNew']
        },
        
        meetsRequirements () {
            return this.requirements && this.requirements.meetsRequirements(this.group, this.section);
        },
        badgeColor () {
            if (this.meetsRequirements) {
                return 'blue';
            }
            return 'yellow'
        },
        badgeText () {
            if (!this.requirements) {
                return 'loading...'
            }
            if (this.meetsRequirements) {
                return 'Meets Requirements';
            }
            const unmetCount = this.requirements.unmetRequirements(this.group, this.section).length;
            return `${unmetCount} requirement${unmetCount > 1 ? 's' : ''} to meet`
        }
    },
    watch: {
        group: {
            immediate: true,
            handler: function (to) {
                console.log({to})
                if (!to.expert_panel) {
                    return;
                }
                if (to.expert_panel.type.name == 'GCEP') {
                    this.requirements = new GcepRequirements();
                    return;
                }
                this.requirements = new VcepRequirements();
            }
        }
    },
    methods: {

    }
}
</script>
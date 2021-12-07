<template>
    <div>
        <popper arrow>
            <template v-slot:content>
                <ul>
                    <li v-for="(req, idx) in evaledRequirements" :key="idx" class="flex space-x-2">
                        <icon-checkmark class="text-green-600" v-if="req.isMet"></icon-checkmark>
                        <icon-exclamation class="text-yellow-400" v-else></icon-exclamation>
                        <div>{{req.label}}</div>
                    </li>
                </ul>
            </template>
            <badge :color="badgeColor" class="cursor-pointer">
                {{badgeText}}
            </badge>
        </popper>
    </div>
</template>
<script>
import Popper from "vue3-popper"
import {VcepRequirements, GcepRequirements} from '@/domain/index'
import configs from '@/configs'

export default {
    name: 'RequirementsBadge',
    components: {
        Popper
    },
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
        },
        evaledRequirements() {
            if (!this.requirements) {
                return 'loading...'
            }
            return this.requirements.checkRequirements(this.group, this.section);
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
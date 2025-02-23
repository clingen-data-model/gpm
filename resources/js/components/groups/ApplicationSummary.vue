<template>
    <div class="p-4 my-4 mb-8 border rounded-xl bg-white" v-if="group.is_ep">
        <h2 class="mb-2">
            Application Summary
            <span v-if="applicationStarted && group.is_vcep_or_scvcep">
                - {{this.currentMenuItem.title}}
            </span>
        </h2>
        <!-- <ul class="px-8" v-if="applicationStarted"> -->
        <ul class="px-8">
            <li v-for="(item, itemIdx) in menu" :key="itemIdx" class="py-1 flex justify-between border-b border-gray-300 my-2">
                <div class="flex justify-between" :class="{'w-full': !isStep(item)}">
                    <div class="w-56 text-lg" :class="{'text-gray-400': (isStep(item) && item.isDisabled(group))}">
                        {{item.title}}
                    </div>
                    <div>
                        <requirements-badge :section="item" v-if="!isStep(item)" />
                        <icon-lock v-if="(isStep(item) && item.isDisabled(group))" class="inline text-gray-400" :height="14" :width="14"/>

                        <div v-if="isStep(item) && item.isComplete(group)">
                            <badge color="green">Approved</badge>
                        </div>
                    </div>
                </div>
                <div v-if="isCurrentStep(item)" class="flex-1">
                    <ul>
                        <li v-for="(section, sectionIdx) in item.sections" :key="sectionIdx" class="pb-2 flex justify-between my-2">
                            <div>{{section.title}}</div>
                            <requirements-badge :section="section" />
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
        <div class="mt-4" v-if="hasAnyPermission(['ep-applications-manage', ['application-edit', group]])">
            <router-link v-if="group.uuid" :to="applicationRoute">
                <button class="btn blue btn-lg">Go to application</button>
            </router-link>
        </div>
    </div>
</template>
<script>
import RequirementsBadge from "@/components/expert_panels/RequirementsBadge.vue";
import { getApplicationForGroup } from "@/composables/use_application.js";

export default {
    name: 'ApplicationSummary',
    components: {
        RequirementsBadge,
    },
    props: {
        group: {
            required: true,
            type: Object
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        applicationStarted () {
            return true;
        },
        application() {
            return getApplicationForGroup(this.group);
        },
        currentStep () {
            return this.group.expert_panel.current_step;
        },
        applicationRoute () {
            return {
                name: 'GroupApplication', 
                params: {uuid: this.group.uuid},
                hash: `#${this.currentMenuItem.id}`
            }
        },
        currentMenuItem () {
            return this.application.getStep(this.group.expert_panel.current_step)
        },
        menu () {
            if (this.application.steps.length > 1) {
                return this.application.steps;
            }
            return this.application.steps[0].sections;
        }
    },
    methods: {
        isStep (menuItem) {
            return Boolean(menuItem.sections);
        },
        isCurrentStep(menuItem) {
            if (this.isStep(menuItem) && this.application.getStep(this.group.expert_panel.current_step) === menuItem) {
                return true;
            }
            return false;
        }
    }
}
</script>
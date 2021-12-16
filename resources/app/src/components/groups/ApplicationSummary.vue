<template>
    <div class="p-4 my-4 mb-8 border rounded-xl bg-white" v-if="group.isEp()">
        <h2 class="mb-2">
            Application Summary
            <span v-if="applicationStarted && group.isVcep()">
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
                <!-- <div v-if="currentStep > pageIdx+1">
                    <badge color="green">Approved</badge>
                </div> -->
                <!-- <icon-lock v-if="currentStep < pageIdx+1" class="inline text-gray-400" :height="14" :width="14"/>  -->
            </li>
        </ul>
        <!-- <div v-else>
            <static-alert class="alert text-lg">
                <div v-if="group.isVcep()">
                    <h2>The VCEP application has 4 steps.</h2>
                </div>
                <h3><span v-if="group.isVcep()">1. </span>Define your {{group.expert_panel.type.full_name}}:</h3>
                <ol class="list-decimal pl-8">
                    <li>Choose your long and short base names.</li>
                    <li>Add members to your expert panel and assign them roles and permissions.</li>
                    <li>Describe your scope of work.</li>
                    <li>Members fill out their profile and complete conflict of interest disclosures.</li>
                    <li>Agree to follow the ClinGen operating guidelines.</li>
                </ol>
                <h3 v-if="group.isVcep()">
                    2. Draft your Specifications.
                </h3>
                <ol class="list-decimal pl-8">
                    <li>Go to the CSPEC Registry</li>
                </ol>
            </static-alert>
        </div> -->
        <div class="mt-4">
            <router-link v-if="group.uuid" :to="applicationRoute">
                <button class="btn blue btn-lg">Go to application</button>
            </router-link>
        </div>
    </div>
</template>
<script>
import RequirementsBadge from '@/components/expert_panels/RequirementsBadge';
import {GcepApplication, VcepApplication} from '@/domain'

export default {
    name: 'ApplicationSummary',
    components: {
        RequirementsBadge
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
        application () {
            return this.group.isVcep() ? VcepApplication : GcepApplication;
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
            if (this.isStep(menuItem) && this.application.getStep(this.group.expert_panel.current_step) == menuItem) {
                return true;
            }
            return false;
        }
    }
}
</script>
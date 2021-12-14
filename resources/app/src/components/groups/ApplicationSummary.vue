<template>
    <div class="p-4 my-4 mb-8 border rounded-xl bg-white" v-if="group.isEp()">
        <h2 class="mb-2">
            Application Summary
            <span v-if="applicationStarted">
                - {{this.currentMenuItem.label}}
            </span>
        </h2>
        <div v-if="group.isGcep()">
            <div v-if="applicationStarted" class="text-lg">
                <ul class="px-8">
                    <li v-for="(section, sectionIdx) in menu" :key="sectionIdx" class="py-1 flex justify-between border-b border-gray-300 my-2">
                        <div>{{section.label}}</div>
                        <requirements-badge :section="section.id"></requirements-badge>
                    </li>
                </ul>
            </div>
            <static-alert v-else class="alert text-lg">
                <h3>Define your {{group.expert_panel.type.full_name}}:</h3>
                <ol class="list-decimal pl-8">
                    <li>Choose your long and short base names.</li>
                    <li>Add members to your expert panel and assign them roles and permissions.</li>
                    <li>Describe your scope of work.</li>
                    <li>Members fill out their profile and complete conflict of interest disclosures.</li>
                    <li>Agree to follow the ClinGen operating guidelines.</li>
                </ol>
            </static-alert>
        </div>
        <div v-if="group.isVcep()">
            <div v-if="applicationStarted">
                <ul class="px-8">
                    <li v-for="(page, pageIdx) in menu" :key="pageIdx" class="py-1 flex justify-between border-b border-gray-300 my-2">
                        <div class="w-56 text-lg" :class="{'text-gray-400': currentStep < pageIdx+1}">
                            {{page.label}}
                        </div>
                        <div v-if="currentStep == (pageIdx+1)" class="flex-1">
                            <ul>
                                <li v-for="(section, sectionIdx) in page.contents" :key="sectionIdx" class="pb-2 flex justify-between my-2">
                                    <div>{{section.label}}</div>
                                    <requirements-badge :section="section.id"></requirements-badge>
                                </li>
                            </ul>
                        </div>
                        <div v-if="currentStep > pageIdx+1">
                            <badge color="green">Approved</badge>
                        </div>
                        <icon-lock v-if="currentStep < pageIdx+1" class="inline text-gray-400" :height="14" :width="14"/> 
                    </li>
                </ul>
            </div>
        </div>
        <div class="mt-4">
            <router-link v-if="group.uuid" :to="applicationRoute">
                <button class="btn blue btn-lg">Go to application</button>
            </router-link>
        </div>
    </div>
</template>
<script>
import {GcepRequirements, VcepRequirements, gcepMenu, vcepMenu} from '@/domain';
import RequirementsBadge from '@/components/expert_panels/RequirementsBadge';

const gcepRequirements = new GcepRequirements();
const vcepRequirements = new VcepRequirements();

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
        requirements () {
            if (this.group.isVcep()) {
                return vcepRequirements;
            }
            if (this.group.isGcep()) {
                return gcepRequirements;
            }

            throw new TypeError('Unexepected group type.')
        },
        menu () {
            if (this.group.isVcep()) {
                return vcepMenu;
            }
            if (this.group.isGcep()) {
                return gcepMenu;
            }
            throw TypeError('Unexpected group type.')
        },
        currentStep () {
            return this.group.expert_panel.current_step;
        },
        applicationRoute () {
            console.log(this.currentStep);
            console.log(this.menu);
            return {
                name: 'GroupApplication', 
                params: {uuid: this.group.uuid},
                hash: `#${this.currentMenuItem.id}`
            }
        },
        currentMenuItem () {
            switch (this.currentStep) {
                case 1:
                    return this.menu[0];
                case 4:
                    return this.menu[2]
                case 2:
                case 3:
                default:
                    return this.menu[1]
            }
        }
    },
    methods: {

    }
}
</script>
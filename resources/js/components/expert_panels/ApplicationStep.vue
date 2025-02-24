<template>
    <div class="application-step" :id="id">
        <div class="header flex justify-between items-center" v-if="title">
            <h2 :class="{ 'text-gray-400': disabled }">
                {{ title }}
                <div class="inline" v-if="disabled">
                    <popover arrow hover>
                        <template #content>
                            <small
                                class="text-sm"
                                v-html="lockedContent"
                            ></small>
                        </template>
                        <icon-lock class="inline" />
                    </popover>
                </div>
            </h2>
            <div class="inline" v-if="approved">
                <popover hover arrow>
                    <template #content>
                        Make changes from the
                        <router-link
                            :to="{
                                name: 'GroupDetail',
                                parmas: { uuid: group.uuid },
                            }"
                            >group's detail screen</router-link
                        >
                        <br /><small>Changes may require re-approval.</small>
                    </template>
                    <badge color="green">Approved</badge>
                </popover>
            </div>
            <badge v-if="underReview" color="yellow">Under Review</badge>
        </div>

        <div class="relative">
            <div class="step-contents">
                <slot></slot>
            </div>
            <ApplicationSubmitButton
                v-if="showSubmitButton"
                class="border-t"
                :step="step"
            />
            <div
                class="z-20 absolute top-0 bottom-0 left-0 right-0 bg-white/50"
                v-if="disabled || approved"
            />
        </div>
    </div>
</template>
<script>
import ApplicationSubmitButton from '@/components/expert_panels/ApplicationSubmitButton.vue'
import { getApplicationForGroup } from "@/composables/use_application.js";

export default {
    name: 'ApplicationStep',
    components: {
        ApplicationSubmitButton
    },
    props: {
        title: {
            type: String,
            required: false,
            default: null
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false
        },
        id: {
            type: String,
            required: true
        },
        noSubmit: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        application() {
            return getApplicationForGroup(this.group);
        },
        step () {
            return this.application.getStep(this.id);
        },
        showSubmitButton () {
            return !this.noSubmit;
        },
        lockedContent () {
            if (this.underReview) {
                return 'Applications pending review cannot be upated.';
            }

            if (this.step.isComplete(this.group)) {
                return `This step is approved.<br> Make changes from the <a href="/groups/${this.group.uuid}">group's detail screen</a><br><small>Changes may require re-approval.</small>`
            }

            return 'This step is not yet available.';   
        },
        approved () {
            return this.step.isComplete(this.group)
        },
        underReview () {
            return this.group.expert_panel.hasPendingSubmissionForStep(this.titleCase(this.id))
        }
    }
}
</script>
<style lang="postcss" scoped>
    .application-step {
        @apply my-8 bg-white shadow-lg relative;
    }
    .application-step:first-child {
        @apply mt-4 border-t-0;
    }

    .application-step > .header{
        @apply py-2 px-4;
        @apply bg-gray-50 border-t border-b;
        /* @apply border border-gray-300 border-b-0 */
    }
</style>
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
<template>
  <div :id="id" class="application-step">
    <div v-if="title" class="header flex justify-between items-center">
      <h2 :class="{ 'text-gray-400': disabled }">
        {{ title }}
        <div v-if="disabled" class="inline">
          <popover arrow hover>
            <template #content>
              <small
                class="text-sm"
                v-html="lockedContent"
              />
            </template>
            <icon-lock class="inline" />
          </popover>
        </div>
      </h2>
      <div v-if="approved" class="inline">
        <popover hover arrow>
          <template #content>
            Make changes from the
            <router-link
              :to="{
                name: 'GroupDetail',
                parmas: { uuid: group.uuid },
              }"
            >
              group's detail screen
            </router-link>
            <br><small>Changes may require re-approval.</small>
          </template>
          <badge color="green">
            Approved
          </badge>
        </popover>
      </div>
      <badge v-if="underReview" color="yellow">
        Under Review
      </badge>
    </div>

    <div class="relative">
      <div class="step-contents">
        <slot />
      </div>
      <ApplicationSubmitButton
        v-if="showSubmitButton"
        class="border-t"
        :step="step"
      />
      <div
        v-if="disabled || approved"
        class="z-20 absolute top-0 bottom-0 left-0 right-0 bg-white/50"
      />
    </div>
  </div>
</template>
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
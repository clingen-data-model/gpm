<script>
import { formatDate } from '@/date_utils'

export default {
    props: {
        step: {
            type: Number,
            required: true,
        },
        application: {
            type: Object,
            required: true
        }
    },
    data() {
        return {

        }
    },
    computed: {
        dateInitiated () {
            return formatDate(this.application.date_initiated)
        },
        approvalDate() {
            if (this.application.approvalDateForStep(this.step)) {
                return formatDate(this.application.approvalDateForStep(this.step))
            }

            return ' '
        },
        displayClass () {
            const classes = [];
            if (this.isApproved) {
                classes.push('approved');
            } else if (this.isCurrentStep) {
                classes.push('active');
            }
            if (this.isFirstStep) {
                classes.push('rounded-l')
            }
            if (this.isLastStep) {
                classes.push('rounded-r')
            }
            return classes.join(' ');
        },
        isCurrentStep () {
            return Number.parseInt(this.application.current_step) === Number.parseInt(this.step)
        },
        isApproved () {
            return this.application.stepIsApproved(this.step);
        },
        isFirstStep () {
            return Number.parseInt(this.step) === 1;
        },
        isLastStep () {
            const lastStep = Math.max(...this.application.steps);
            return Number.parseInt(this.step) === Number.parseInt(lastStep);
        }
    },
    methods: {
    }
}
</script>
<template>
  <div class="flex-1">
    <div class="progress-step" :class="displayClass" />
    <div class="flex flex-row-reverse justify-between bg-transparent mt-1">
      <div :class="{'-mr-10': !isLastStep}">
        {{ approvalDate }}
      </div>
      <div v-if="isFirstStep">
        {{ dateInitiated }}
      </div>
    </div>
  </div>
</template>
<style lang="postcss">
    .progress-step {
        @apply h-4 bg-green-100 border border-green-200 flex-1;
    }
    .progress-step.approved {
        @apply bg-green-500 border-green-600;
    }
    .progress-step.active {
        @apply ring-green-400 ring-2 border-green-400 border-2;
    }
</style>

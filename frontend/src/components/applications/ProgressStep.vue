<style lang="postcss">
    .progress-step {
        @apply h-4 bg-green-100 border border-green-200 flex-1
    }
    .progress-step.approved {
        @apply bg-green-500 border-green-600
    }
    .progress-step.active {
        @apply ring-green-400 ring-2 border-green-400 border-2
    }
</style>
<template>
    <div class="flex-1">
        <div class="progress-step" :class="displayClass"></div>
        <div class="flex flex-row-reverse justify-between bg-transparent mt-1">
            <div v-if="isApproved" :class="{'-mr-10': !isLastStep}">{{approvalDate}}</div>
            <div v-if="isFirstStep">{{dateInitiated}}</div>
        </div>
    </div>
</template>
<script>
import { formatDate } from '../../date_utils'

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
            console.log(this.application.approval_dates)
            return formatDate(this.application.approval_dates['step '+this.step])
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
            console.info(`step ${this.step} classes: `, classes)
            return classes.join(' ');
        },
        isCurrentStep () {
            return this.application.current_step == this.step
        },
        isApproved () {
            return this.application.approval_dates 
                && typeof this.application.approval_dates[`step ${this.step}`] != 'undefined'
        },
        isFirstStep () {
            return this.step == 1;
        },
        isLastStep () {
            const lastStep = this.application.ep_type_id == 1 ? 1 : 4;
            return this.step == lastStep;
        }
    },
    methods: {
    }
}
</script>
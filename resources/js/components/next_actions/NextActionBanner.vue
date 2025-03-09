<script>
import is_validation_error from '@/http/is_validation_error';
export default {
    props: {
        application: {
            type: Object, 
            required: true
        },
        nextAction: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            showModal: false,
            dateCompleted: null,
            errors: {}
        }
    },
    methods: {
        clearForm() {
            this.dateCompleted = null;
        },
        async markComplete () {
            try {
                await this.$store.dispatch('applications/completeNextAction', 
                    {
                        application: this.application, 
                        nextAction: this.nextAction, 
                        dateCompleted: this.dateCompleted
                    })
                this.clearForm();
                this.$emit('completed');
                this.showModal = false;
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                }
            }
        },
        cancel () {
            this.clearForm()
            this.showModal = false;
            this.$emit('canceled');
        }
    }
}
</script>
<template>
    <div>
        <div class="px-2 py-2 pl-3 bg-blue-100 rounded flex justify-between items-start text-blue-800">
            <div class="leading-5 w-4/5">
                <span v-html="nextAction.entry"></span>
            </div>
            <button class="btn blue btn-xs block flex-initial self-center" @click="showModal = true">Mark completed</button>        
        </div>

        <modal-dialog v-model="showModal" title="Complete next action">
            <dictionary-row label="Action">
                <div v-html="nextAction.entry"></div>
            </dictionary-row>
            <object-dictionary 
                :obj="nextAction"
                :show="['date_created', 'step', 'target_date']"
                :dates="['target_date', 'date_created']"
            ></object-dictionary>

            <input-row v-model="dateCompleted" label="Date Completed" type="date" :errors="errors.date_completed"></input-row>
            
            <button-row>
                <button class="btn white" @click="cancel">Cancel</button
                ><button class="btn blue" @click="markComplete">Mark Complete</button>
            </button-row>
        </modal-dialog>

    </div>
</template>   
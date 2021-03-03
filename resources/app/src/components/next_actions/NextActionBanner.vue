<template>
    <div>
        <div class="px-2 py-2 pl-3 bg-blue-200 rounded flex justify-between text-blue-800">
            <div class="leading-6">
                <strong>Next Action:</strong> {{nextAction.entry}}
            </div>
            <button class="btn blue btn-xs" @click="showModal = true">Mark completed</button>        
        </div>

        <modal-dialog v-model="showModal">
            <h4 class="text-lg">Complete next action</h4>
            <object-dict 
                :obj="nextAction"
                :show="['date_created', 'entry', 'step', 'target_date']"
                :dates="['target_date', 'date_created']"
            ></object-dict>

            <input-row  label="Date Completed" v-model="dateCompleted" type="date" :errors="errors.date_completed"></input-row>
            
            <button-row>
                <button class="btn white" @click="cancel">Cancel</button
                ><button class="btn blue" @click="markComplete">Mark Complete</button>
            </button-row>
        </modal-dialog>

    </div>
</template>
<script>
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
            await this.$store.dispatch('applications/completeNextAction', 
                {
                    application: this.application, 
                    nextAction: this.nextAction, 
                    dateCompleted: this.dateCompleted
                })
            this.clearForm();
            this.$emit('completed');
            this.showModal = false;
        },
        cancel () {
            this.clearForm()
            this.showModal = false;
            this.$emit('canceled');
        }
    }
}
</script>   
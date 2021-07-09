<template>
    <div>
        <dict-row label="Action">
            <div v-html="nextAction.entry"></div>
        </dict-row>
        <object-dict 
            :obj="nextAction"
            :show="['date_created', 'step', 'target_date']"
            :dates="['target_date', 'date_created']"
        ></object-dict>

        <input-row  label="Date Completed" v-model="dateCompleted" type="date" :errors="errors.date_completed"></input-row>
        
        <button-row>
            <button class="btn white" @click="cancel">Cancel</button
            ><button class="btn blue" @click="markComplete">Mark Complete</button>
        </button-row>
    </div>
</template>
<script>
import is_validation_error from '@/http/is_validation_error'
import {mapGetters} from 'vuex'

export default {
    name: 'ComponentName',
    props: {
        nextAction: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            errors: [],
            dateCompleted: null
        }
    },
    computed: {
        ...mapGetters({
            application: 'applications/currentItem'
        })
    },
    methods: {
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
         clearForm() {
            this.dateCompleted = null;
        },
        cancel () {
            this.clearForm()
            this.$emit('canceled');
        }
    }
}
</script>
<template>
    <div class="log-entry-form">
        <h4 class="pb-2 border-b my-2 text-xl">Add Log Entry</h4>
        <input-row label="Log Date" v-model="newEntry.log_date" :errors="errors.log_date" type="date"></input-row>
        <step-input v-model="newEntry.step" :errors="errors.step" v-if="application.ep_type_id == 2"></step-input>
        <input-row label="Entry" :errors="errors.entry">
            <textarea cols="30" rows="10" v-model="newEntry.entry"></textarea>
        </input-row>
        <button-row>
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Save</button>
        </button-row>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import { formatDate } from '../../date_utils'
import StepInput from '../forms/StepInput'

export default {
    components: {
        StepInput
    },
    data() {
        return {
            newEntry: {
                log_date: new Date(),
                step: null,
                entry: null
            },
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            application: 'applications/currentItem'
        })
    },
    methods: {
        initNewEntry () {
            this.newEntry ={
                log_date: formatDate(new Date),
                step: null,
                entry: null
            }
        },
        cancel() {
            this.initNewEntry();
            this.$emit('canceled');
        },
        async save() {
            try {
                await this.$store.dispatch('applications/addLogEntry', {application: this.application, logEntryData: this.newEntry})
                this.initNewEntry();
                this.$emit('saved');
            } catch (error) {
                if (error.response && error.response.status == 422 && error.response.data.errors) {
                    this.errors = error.response.data.errors
                    return;
                }
            }
        }
    }
}
</script>
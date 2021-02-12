<template>
    <div class="log-entry-form">
        <h4 class="pb-2 border-b my-2 text-xl">Add Log Entry</h4>
        <input-row label="Log Date" v-model="newEntry.log_date" :errors="errors.log_date" type="date"></input-row>
        <step-input v-model="newEntry.step" :errors="errors.step" v-if="application.ep_type_id == 2"></step-input>
        <input-row label="Entry" :errors="errors.entry">
            <textarea cols="30" rows="10" v-model="newEntry.entry"></textarea>
        </input-row>
        <div class="btn-row">
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Save</button>
        </div>
    </div>
</template>
<script>
import { formatDate } from '../../date_utils'
import StepInput from '../forms/StepInput'

export default {
    components: {
        StepInput
    },
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    data() {
        return {
            newEntry: {
                log_date: formatDate(new Date),
                step: null,
                entry: null
            },
            errors: {}
        }
    },
    computed: {
        application () {
            return this.$store.getters.getApplicationByUuid(this.uuid) || {};
        }
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
        save() {
            console.error('Not implemented');
            this.initNewEntry();
            this.$emit('saved');
        }
    }
}
</script>
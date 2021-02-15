<template>
    <div>
        <h4 class="pb-2 border-b my-2 text-xl">Add Next Action</h4>
        <input-row label="Creation Date" :errors="errors.date_created">
            <input type="date" v-model="newAction.date_created">
        </input-row>

        <step-input v-model="newAction.step" :errors="errors.step" v-if="application.ep_type_id == 2"></step-input>

        <input-row label="Target" Date="" :errors="errors.target_date">
            <input type="date" v-model="newAction.target_date">
        </input-row>

        <input-row label="Entry" :errors="errors.entry">
            <textarea name="" id="" cols="30" rows="5" v-model="newAction.entry"></textarea>
        </input-row>    

        <label class="ml-36">
            <input type="checkbox" v-model="completed"> This is already completed
        </label>

        <input-row label="Date Completed" :errors="errors.date_completed" class="ml-36" v-if="completed">
            <input type="date" v-model="newAction.date_completed">
        </input-row>

        <div class="btn-row">
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Save</button>
        </div>
    </div>
</template>
<script>
import {formatDate} from '../../date_utils'
import StepInput from '../forms/StepInput'
import { v4 as uuid4 } from 'uuid';

export default {
    name: 'NextActionForm',
    components: {
        StepInput
    },
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    emits: [
        'saved',
        'closed',
        'formCleard'
    ],
    data() {
        return {
            errors: {},
            newAction: {
                uuid: null,
                date_created: null,
                step: null,
                date_completed: null,
                entry: null
            },
            completed: false
        }
    },
    computed: {
        steps () {
            return [1,2,3,4];
        },
        application () {
            return this.$store.getters.getApplicationByUuid(this.uuid) || {};
        }
    },
    methods: {
        async save() {
            try {
                await this.$store.dispatch('addNextAction', {application: this.application, nextActionData: this.newAction})
                this.$emit('saved')
                this.clearForm();
            } catch (error) {
                if (error.response && error.response.status == 422 && error.response.data.errors) {
                    this.errors = error.response.data.errors
                    return;
                }
            }
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        clearForm() {
            this.initNewAction();
            this.$emit('formCleard')
        },
        initNewAction () {
            this.newAction = {
                // uuid: this.uuid,
                date_created: formatDate(new Date()),
                step: null,
                target_date: null,
                date_completed: null,
                entry: null,
            };      
        }
    },
    unmounted() {
        this.initNewAction()
    },
    mounted() {
        this.initNewAction()
    }
}
</script>
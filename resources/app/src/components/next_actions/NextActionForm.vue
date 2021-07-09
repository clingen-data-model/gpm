<template>
    <form-container>
        <h2 class="block-title">{{title}}</h2>
        <input-row label="Creation Date" :errors="errors.date_created" type="date" v-model="newAction.date_created"></input-row>

        <step-input v-model="newAction.step" :errors="errors.step" v-if="application.ep_type_id == 2"></step-input>

        <input-row label="Target" Date="" :errors="errors.target_date" type="date" v-model="newAction.target_date"></input-row>

        <input-row label="Entry" :errors="errors.entry">
            <rich-text-editor v-model="newAction.entry"></rich-text-editor>
        </input-row>

        <input-row label="Assigned To" :errors="assignmentErrors">
            <select id="" v-model="newAction.assigned_to">
                <option :value="null">Select...</option>
                <option value="Expert Panel">Expert Panel</option>
                <option value="CDWG OC">CDWG OC</option>
            </select>
            &nbsp;&nbsp;
            <input type="text" label="Name" v-model="newAction.assigned_to_name" placeholder="Name (optional)">
        </input-row>

        <div class="ml-4">
        </div>

        <label class="ml-36">
            <input type="checkbox" v-model="completed"> This is already completed
        </label>

        <input-row label="Date Completed" :errors="errors.date_completed" class="ml-36" v-if="completed">
            <input type="date" v-model="newAction.date_completed">
        </input-row>

        <button-row>
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Save</button>
        </button-row>
    </form-container>
</template>
<script>
import StepInput from '../forms/StepInput'
import {mapGetters} from 'vuex'
import RichTextEditor from '../forms/RichTextEditor'
import {formatDate} from '@/date_utils'

export default {
    name: 'NextActionForm',
    components: {
        StepInput,
        RichTextEditor
    },
    props: {
        id: {
            required: false,
            default: null
        }
    },
    emits: [
        'saved',
        'closed',
        'formCleared',
        'canceled'
    ],
    data() {
        return {
            errors: {},
            newAction: {
                date_created: new Date(),
                step: null,
                date_completed: null,
                entry: '',
                assigned_to: null,
                assigned_to_name: null
            },
            completed: false
        }
    },
    computed: {
        ...mapGetters({application: 'applications/currentItem'}),
        title () {
            return this.newAction.id ? 'Update Action' : 'Add Next Action';
        },
        steps () {
            return [1,2,3,4];
        },
        assignmentErrors () {
            let errors = [];
            if (this.errors.assigned_to) {
                errors = [...errors, ...this.errors.assigned_to]
            }
            if (this.errors.assigned_to_name) {
                errors = [...errors, ...this.errors.assigned_to_name]
            }
            return errors;
        }
    },
    watch: {
        id: {
            immediate: true,
            handler: function() {
                const action = this.findAction();
                if (action) {
                    this.syncAction(action)
                }
            }
        },
        application: {
            immediate: true,
            handler: function () {
                const action = this.findAction();
                if (action) {
                    this.syncAction(action);
                }
            }
        }
    },
    methods: {
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        clearForm() {
            console.log('clearForm()');
            this.initNewAction();
            this.$emit('formCleared')
        },
        initNewAction () {
            this.newAction = {
                date_created: new Date(),
                step: null,
                target_date: null,
                date_completed: null,
                entry: '',
                assigned_to: null,
                assigned_to_name: null,
            };      
        },
        findAction () {
            if (this.id === null) {
                return null;
            }
            if (this.application.next_actions) {
                return this.application.next_actions.find(i => i.id == this.id);
            }
        },
        syncAction (action) {
            if (!action) {
                return;
            }
            this.newAction = {
                id: action.id,
                uuid: action.uuid,
                date_created: formatDate(action.created_at),
                step: action.step,
                target_date: formatDate(action.target_date),
                date_completed: formatDate(action.date_completed),
                entry: action.entry ?? '',
                assigned_to: action.assigned_to,
                assigned_to_name: action.assigned_to_name
            }
        },
        async save() {
            console.log('save!');
            console.info('this.newAction', this.newAction);
            try {
                if (this.newAction.id) {
                    await this.$store.dispatch(
                        'applications/updateNextAction', 
                        {
                            application: this.application, 
                            updatedAction: this.newAction,
                        }
                    );
                    this.$emit('saved')
                    this.clearForm();
                } else {
                    await this.$store.dispatch(
                        'applications/addNextAction', 
                        {
                            application: this.application, 
                            nextActionData: this.newAction
                        }
                    );
                    this.$emit('saved')
                    this.clearForm();
                }
            } catch (error) {
                if (error.response && error.response.status == 422 && error.response.data.errors) {
                    this.errors = error.response.data.errors
                    return;
                }
            }
        },
    },
    unmounted() {
        this.initNewAction()
    },
    mounted() {
    }
}
</script>
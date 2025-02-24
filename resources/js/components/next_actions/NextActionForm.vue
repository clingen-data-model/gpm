<script>
import StepInput from '@/components/forms/StepInput.vue'
import {mapGetters} from 'vuex'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import {formatDate} from '@/date_utils'
import configs from '@/configs'

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
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
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
        },
        assignees () {
            return Object.values(configs.nextActions.assignees);
        }
    },
    watch: {
        id: {
            immediate: true,
            handler() {
                const action = this.findAction();
                if (action) {
                    this.syncAction(action)
                }
            }
        },
        application: {
            immediate: true,
            handler () {
                const action = this.findAction();
                if (action) {
                    this.syncAction(action);
                }
            }
        }
    },
    unmounted() {
        this.initNewAction()
    },
    mounted() {
    },
    methods: {
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        clearForm() {
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
                return this.application.next_actions.find(i => i.id === this.id);
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
                if (error.response && error.response.status === 422 && error.response.data.errors) {
                    this.errors = error.response.data.errors
                }
            }
        },
    }
}
</script>
<template>
  <form-container>
    <input-row v-model="newAction.date_created" label="Creation Date" :errors="errors.date_created" type="date" />

    <StepInput v-if="application.expert_panel_type_id === 2" v-model="newAction.step" :errors="errors.step" />

    <input-row v-model="newAction.target_date" label="Target Date" :errors="errors.target_date" type="date" />

    <input-row label="Entry" :errors="errors.entry">
      <RichTextEditor v-model="newAction.entry" />
    </input-row>

    <input-row label="Assigned To" :errors="assignmentErrors">
      <select id="" v-model="newAction.assigned_to">
        <option :value="null">
          Select...
        </option>
        <option
          v-for="i in assignees" :key="i.id"
          :value="i.id"
        >
          {{ i.name }}
        </option>
      </select>
            &nbsp;&nbsp;
      <input v-model="newAction.assigned_to_name" type="text" label="Name" placeholder="Name (optional)">
    </input-row>

    <div class="ml-4" />

    <checkbox  
      v-model="completed" 
      label="This is already completed"
      class="ml-36"
    />

    <input-row v-if="completed" label="Date Completed" :errors="errors.date_completed" class="ml-36">
      <input v-model="newAction.date_completed" type="date">
    </input-row>

    <button-row>
      <button class="btn" @click="cancel">
        Cancel
      </button>
      <button class="btn blue" @click="save">
        Save
      </button>
    </button-row>
  </form-container>
</template>
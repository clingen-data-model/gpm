<template>
    <div class="text-sm">
        <!-- <pre>{{filteredNextActions}}</pre> -->
        <div class="flex justify-between">
            <h3>Next Actions</h3>
            <label>
                <input type="checkbox" v-model="showCompleted">
                Show completed
            </label>
        </div>
        <data-table 
            :fields="fields" 
            :data="filteredNextActions" v-model:sort="tableSort"  
            v-if="filteredNextActions.length > 0"
        >
            <template v-slot:cell-entry="{item}">
                <div  v-html="item.entry"></div>
            </template>
            <template v-slot:cell-assigned_to="{item}">
                <div class="flex">
                    {{item.assignee ? item.assignee.name : '??'}}&nbsp;
                    <span v-if="item.assigned_to_name"> - {{item.assigned_to_name}}</span> 
                </div>
            </template>
            <template v-slot:cell-action="{item}">
                <div class="flex space-x-1">
                    <edit-button @click="$router.push({name: 'EditNextAction', params: {uuid: application.uuid, id: item.id}})"></edit-button>
                    <trash-icon-button @click="initiateDelete(item)"></trash-icon-button>
                    <checkmark-icon 
                        width="20" 
                        height="20"
                        :class="{'text-green-500': Boolean(item.date_completed), 'text-gray-300': !Boolean(item.date_completed)}"
                        v-if="Boolean(item.date_completed)"
                    ></checkmark-icon>
                    <checkmark-button
                        v-else
                        @click.prevent="startCompleting(item)"
                        title="Mark complete"
                    ></checkmark-button>
                </div>
            </template>
        </data-table>
        <div v-else class="well bg-gray-200 rounded border px-4 py-2 border-gray-300">
            There are no pending <span v-if="showCompleted">or completed</span> next actions.
        </div>

        <modal-dialog v-model="showCreateModal" title="Complete next action">
            <complete-next-action-form 
                :next-action="selectedNextAction"
                @canceled="showCreateModal = false"
                @completed="handleCompleted"
            ></complete-next-action-form>
        </modal-dialog>

    </div>
</template>
<script>
import CompleteNextActionForm from '@/components/next_actions/CompleteNextActionForm'
import {mapGetters} from 'vuex'
import EditButton from '@/components/buttons/EditIconButton'
import CheckmarkButton from '@/components/buttons/CheckmarkIconButton'
import CheckmarkIcon from '@/components/icons/IconCheckmark'
import TrashIconButton from '@/components/buttons/TrashIconButton.vue'

export default {
    name: 'NextActions',
    components: {
        CompleteNextActionForm,
        EditButton,
        CheckmarkIcon,
        CheckmarkButton,
        TrashIconButton,
    },
    props: {
        nextActions: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            tableSort: {
                field: 'target_date',
                desc: false
            },
            showCompleted: false,
            showDeleteConfirmation: false,
            selectedNextAction: {},
            errors: {},
            showCreateModal: false,
            fields: [
                {
                    name: 'entry',
                    label: 'Next Action',
                    type: String,
                },
                {
                    name: 'assigned_to',
                    label: 'Assigned to',
                    sortable: true,
                    resolveValue(item) {
                        let str = item.assigned_to;
                        if (item.assigned_to_name) {
                            str +=  `- ${item.assigned_to_name}`;
                        }
                        return str
                    }
                },
                {
                    name: 'target_date',
                    label: 'Due Date',
                    type: Date,
                    sortable: true
                },
                {
                    name: 'action',
                    label: '',
                    sortable: false
                }
            ]
        }
    },
    computed: {
        ...mapGetters ({
            application: 'applications/currentItem'
        }),
        filteredNextActions () {
            if (!this.nextActions) {
                return [];
            }

            if (this.showCompleted) {
                return this.nextActions;
            }

            return this.nextActions.filter(na => !na.date_completed);
        },
    },
    methods: {
        startCompleting(nextAction) {
            this.selectedNextAction = nextAction;
            this.showCreateModal = true;
        },
        handleCompleted() {
            this.$emit('completed');
            this.showCreateModal = false;
        },
        initiateDelete(nextAction) {
            this.$router.push({
                name: 'ConfirmDeleteNextAction', 
                params: {
                    applicationUuid: this.application.uuid, 
                    id: nextAction.id
                }
            });
        }
    }
}
</script>
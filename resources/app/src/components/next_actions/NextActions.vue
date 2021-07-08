<template>
    <div class="text-sm">
        <div class="flex justify-between">
            <h3>Next Actions</h3>
            <label>
                <input type="checkbox" v-model="showCompleted">
                Show completed
            </label>
        </div>
        <data-table :fields="fields" :data="filteredNextActions" v-model:sort="tableSort"  v-if="filteredNextActions.length > 0">
            <template v-slot:cell-entry="{item}">
                <div  v-html="item.entry"></div>
            </template>
            <template v-slot:cell-assigned_to="{item}">
                <div class="flex">
                    <div class="flex-1">{{item.assigned_to}}</div>
                    <div class="flex-1" v-if="item.assigned_to_name">{{item.assigned_to_name}}</div>
                </div>
            </template>
            <template v-slot:cell-action="{item}">
                <div>
                    <input type="checkbox" class="btn btn-xs" @click.prevent="startCompleting(item)" :checked="Boolean(item.date_completed)">
                </div>
            </template>
        </data-table>
        <div v-else class="well bg-gray-200 rounded border px-4 py-2 border-gray-300">
            There are no pending <span v-if="showCompleted">or completed</span> next actions.
        </div>

        <modal-dialog v-model="showModal" title="Complete next action">
            <complete-next-action-form 
                :next-action="selectedNextAction"
                @canceled="showModal = false"
                @completed="handleCompleted"
            ></complete-next-action-form>
        </modal-dialog>

    </div>
</template>
<script>
import CompleteNextActionForm from '@/components/next_actions/CompleteNextActionForm'

export default {
    name: 'NextActions',
    components: {
        CompleteNextActionForm,
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
            selectedNextAction: {},
            errors: {},
            showModal: false,
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
        filteredNextActions () {
            if (!this.nextActions) {
                return [];
            }

            if (this.showCompleted) {
                return this.nextActions;
            }

            return this.nextActions.filter(na => !na.date_completed);
        }
    },
    methods: {
        startCompleting(nextAction) {
            this.selectedNextAction = nextAction;
            this.showModal = true;
        },
        handleCompleted() {
            this.$emit('completed');
            this.showModal = false;
        }
    }
}
</script>
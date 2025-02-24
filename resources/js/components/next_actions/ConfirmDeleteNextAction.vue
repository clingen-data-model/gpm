<script>
import {mapGetters} from 'vuex'
import { formatDate } from '@/date_utils'
import is_validation_error from '@/http/is_validation_error'

export default {
    name: 'ConfirmDeleteNextAction',
    props: {
        uuid: {
            required: true,
            type: String
        },
        id: {
            required: true,
            type: String,
        }
    },
    data() {
        return {
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
        nextAction () {
            if (!this.application.next_actions) {
                return {};
            }
            const next_action = this.application.next_actions.find(na => na.id === this.id);
            return next_action || {};
        },
        flattenedErrors () {
            return Object.values(this.errors).flat();
        },
        targetDate() {
            return formatDate(this.nextAction.targetDate);
        }
    },
    watch: {
        nextAction: {
            immediate: true,
            handler () {
                if (!this.nextAction.id) {
                    this.$router.go(-1);
                }
            }
        }
    },
    methods: {
        formatDate(date) {
            return formatDate(date);
        },
        async commitDelete()
        {
            try {
                await this.$store.dispatch('applications/deleteNextAction', 
                                    {
                                        application: this.application, 
                                        nextAction: this.nextAction
                                    });
                this.$router.go(-1);
            } catch ( error ) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors;
                }
                this.errors = {a: error.message};
                
            }
        }
    }
}
</script>
<template>
    <div>            
        <h2>You are about to delete the following next action:</h2>
        <div v-if="nextAction" class="border-y py-2">
            <div class="ml-4 my-3 text-sm">
                Created on: <strong>{{ formatDate(nextAction.created_at) }}</strong>
            </div>
            <blockquote class="mb-4">
                <div v-html="nextAction.entry"></div>
            </blockquote>
            <div v-if="nextAction.assignee" class="ml-4 my-1 text-sm">
                Assigned to: 
                <strong>
                    <span v-if="nextAction.assigned_to_name">{{ nextAction.assigned_to_name }} in </span> 
                    {{ nextAction.assignee.name }}
                </strong>
            </div>
            <div v-if="nextAction.target_date" class="ml-4 mt-1 mb-4 text-sm">
                Target Date: <strong>{{ formatDate(nextAction.target_date) }}</strong>
            </div>
        </div>

    <div>This can not be undone. Are you sure you want to continue?</div>
        
    <button-row submitText="Delete Log Entry" @canceled="$router.go(-1)" @submitted="commitDelete"></button-row>
    </div>
</template>
<style lang="postcss" scope>
    blockquote {
        @apply mt-4 ml-4 border-l-4 pl-2 text-gray-700;
        font-size: 1.1rem;
    }
</style>
<template>
    <div>
        <header class="flex justify-between items-center">
            <h4>Description of Scope</h4>
            <edit-icon-button 
                v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing"
                @click="$emit('update:editing', true)"
            ></edit-icon-button>
        </header>
        <div class="mt-2">
            <transition name="fade" mode="out-in">
                <input-row :vertical="true" label="Describe the scope of work of the Expert Panel including the disease area(s), genes being addressed, and any specific rational for choosing the condition(s)." v-if="editing" :errors="errors.scope_description">
                    <textarea 
                        class="w-full" 
                        rows="10" 
                        v-model="scopeDescription"
                    />
                </input-row>
                <div v-else>
                    <markdown-block 
                        v-if="scopeDescription" 
                        :markdown="scopeDescription">
                    </markdown-block>
                    <p class="well cursor-pointer" v-else @click="showForm">
                        A description of expertise has not yet been provided.
                    </p>
                </div>
            </transition>
        </div>
    </div>
</template>
<script>
import Group from '@/domain/group'

export default {
    name: 'scopeDescriptionForm',
    props: {
        group: {
            type: Group,
            required: true
        },
        editing: {
            type: Boolean,
            required: false,
            default: true
        },
        errors: {
            type: Object,
            required: false,
            default: () => ({})
        }
    },
    emits: [
        'update:editing',
        'saved',
        'canceled',
        'input',
    ],
    computed: {
        scopeDescription: { 
            get: function () {
                return this.group.expert_panel.scope_description
            }, 
            set: function (value) {
                const groupCopy = this.group.clone();
                groupCopy.expert_panel.scope_description = value;
                this.$emit('input', groupCopy);
            }
        }
    }
}
</script>
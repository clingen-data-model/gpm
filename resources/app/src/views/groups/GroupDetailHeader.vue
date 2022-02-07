<template>
    <header class="pb-4">
        <group-breadcrumbs />
        <h1 class="flex justify-between items-center">
            <div>
                {{group.displayName}} <badge :color="group.statusColor" class="text-xs">{{group.status ? group.status.name : 'loading...'}}</badge>
                <note v-if="hasRole('super-user')" class="font-normal">
                    group.id: {{group.id}}
                    <span v-if="group.isEp()"> | expertPanel.id: {{group.expert_panel.id}}</span>
                </note>
            </div>
            <button 
                v-if="hasAnyPermission(['groups-manage', ['info-edit', group]])" 
                @click="$emit('showEdit')"
                class="btn btn-xs" 
            >
                Edit Group Info
            </button>
        </h1>
        <dictionary-row label="Chairs:">
            <template v-slot:label><strong>Chairs:</strong></template>
            <div v-if="group.chairs.length > 0">
                {{group.chairs.map(c => c.person.name).join(', ')}}
            </div>
            <div class="text-gray-500" v-else>
                None assigned
            </div>
        </dictionary-row>
        <dictionary-row label="Coordinators:">
            <template v-slot:label><strong>Coordinators:</strong></template>
            <div v-if="coordinators.length > 0">
                {{coordinators.map(c => c.person.name).join(', ')}}
            </div>
            <div class="text-gray-500" v-else>
                None assigned
            </div>
        </dictionary-row>
    </header>
</template>
<script>
import Group from '@/domain/group'

export default {
    name: 'GroupDetailHeader',
    props: {
        group: {
            type: Group,
            required: true,
        }
    },
    emits: [
        'showEdit'
    ],
    computed: {
        // NOTE: not sure why this is necessary. group.charis and group.coordinators
        // are identical getters except for the role being filtered on. But since 
        // both work when the data is first loaded I don't know why chairs updates on 
        // member change and coordinators.
        coordinators: function() {
            if (!this.group) {
                return [];
            }
            return this.group.members.filter(m => m.hasRole('coordinator'));
        }
    },
}
</script>
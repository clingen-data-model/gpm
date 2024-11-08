<script setup>
    import { computed, ref } from 'vue'
    import { hasPermission } from '../../auth_utils';
    import CredentialsView from '../people/CredentialsView.vue';
    import ExpertisesView from '../people/ExpertisesView.vue';
    import { formatDate } from '@/date_utils'

    const props = defineProps({
        members: {
            required: true,
            type: Array
        },
    });

    const tableSort = ref({
        field: 'roles',
        desc: false
    });

    const fields = ref([
        {
            name: 'name',
            sortable: true,
            label: 'Name',
            sortFunction: (a, b) => {
                let cmp = a.last_name.localeCompare(b.last_name);
                if (cmp === 0) {
                    cmp = a.first_name.localeCompare(b.first_name);
                }
                return cmp;
            }
        },
        {
            name: 'legacy_credentials',
            sortable: false,
            label: 'Credentials',
        },
        {
            name: 'roles',
            sortable: true,
            label: 'Roles',
            sortFunction: (a, b) => {
                return a.role_priority - b.role_priority;
            },
        },
        {
            name: 'legacy_expertise',
            sortable: false,
            label: 'Expertise',
        },
        {
            name: 'institution',
            sortable: false,
            label: 'Institution',
        },
    ]);

    if (hasPermission('ep-applications-manage')) {
        fields.value.push({
            name: 'coi_completed',
            type: String,
            sortable: true,
            label: 'COI Completed',
        });
    }

    const tableRows = computed( () => {
        return props.members.map(m => {
            const roles = m.roles.toSorted((a, b) => a.id - b.id);
            const retVal = {
                id: m.id,
                first_name: m.person.first_name,
                last_name: m.person.last_name,
                name: m.person.name,
                institution: m.person.institution?.name,
                legacy_credentials: m.person.legacy_credentials,
                legacy_expertise: m.legacy_expertise,
                roles: roles,
                role_priority: Math.min(...roles.map(r => r.id)),
                person: m.person
            }
            if (hasPermission('ep-applications-manage')) {
                retVal.coi_completed = formatDate(m.coi_last_completed);
            }
            return retVal;
        });
    });

</script>
<template>
    <div>
        <data-table
            :fields="fields"
            :data="tableRows"
            v-model:sort="tableSort"
        >
            <template v-slot:cell-roles="{item}">
                <span>{{ item.roles.map(r => r.display_name).join(', ') }}</span>
            </template>
            <template v-slot:cell-legacy_credentials="{item}">
                <CredentialsView :person="item.person" />
            </template>
            <template v-slot:cell-legacy_expertise="{item}">
                <ExpertisesView :person="item.person" :legacyExpertise="item.legacy_expertise" />
            </template>
        </data-table>
    </div>
</template>

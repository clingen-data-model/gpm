<script setup>
    import { computed, ref, watch } from 'vue'
    import {hasPermission} from '../../auth_utils';
    import CredentialsView from '../people/CredentialsView.vue';
    import ExpertisesView from '../people/ExpertisesView.vue';
    import { formatDate } from '@/date_utils'

    const props = defineProps({
        members: {
            required: true,
            type: Array
        },
    });

    const rolePriorities = {
        'chair': 1,
        'coordinator': 2,
        'biocurator': 3,
        'expert': 4,
    };

    const fields = ref([
        {
            name: 'name',
            sortable: true,
            label: 'Name',
        },
        {
            name: 'legacy_credentials',
            sortable: false,
            label: 'Credentials',
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
        {
            name: 'roles',
            sortable: true,
            label: 'Group Roles',
        },
    ]);

    const sort = ref({
        field: 'name',
        desc: false
    });

    if (hasPermission('ep-applications-manage')) {
        fields.value.push({
            name: 'coi_completed',
            sortable: false,
            label: 'COI Completed',
        });
    }

    const tableRows = computed( () => {
        return props.members.map(m => {
            const retVal = {
                id: m.id,
                first_name: m.person.first_name,
                last_name: m.person.last_name,
                name: m.person.name,
                institution: m.person.institution?.name,
                credentials: m.person.legacy_credentials,
                legacy_expertise: m.legacy_expertise,
                roles: m.roles.map(r => r.name).join(', '),
                person: m.person
            }
            if (hasPermission('ep-applications-manage')) {
                retVal.coi_completed = formatDate(m.coi_last_completed);
            }

            return retVal;
        });
    })

</script>
<template>
    <div>
        <data-table 
            :fields="fields" 
            :data="tableRows"
            v-model:sort="sort"
            :row-class="() => 'cursor-pointer'"
        >
            <template v-slot:cell-legacy_credentials="{item}">
                <CredentialsView :person="item.person" />
            </template>
            <template v-slot:cell-legacy_expertise="{item}">
                <ExpertisesView :person="item.person" :legacyExpertise="item.legacy_expertise" />
            </template>
        </data-table>
    </div>
</template>

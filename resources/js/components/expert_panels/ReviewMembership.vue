<script setup>
    import { computed, ref } from 'vue'
    import CredentialsView from '../people/CredentialsView.vue';
    import ExpertisesView from '../people/ExpertisesView.vue';

    const props = defineProps({
        members: {
            required: true,
            type: Array
        },
    });

    const role_priorities = {
        'Chair': 1,
        'Coordinator': 2,
        'Grant Liaison': 3,
        'Biocurator': 4,
        'Expert': 5,
    };

    const rolePriority = (role) => {
        return role_priorities[role.display_name] ?? Infinity;
    }

    const tableSort = ref({
        field: 'roles',
        desc: false
    });

    const nameSort = (a, b) => {
        let cmp = a.last_name.localeCompare(b.last_name);
        if (cmp === 0) {
            cmp = a.first_name.localeCompare(b.first_name);
        }
        return cmp;
    }

    const fields = ref([
        {
            name: 'name',
            sortable: true,
            label: 'Name',
            sortFunction: nameSort,
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

    const tableRows = computed( () => {
        return props.members.map(m => {
            const roles = m.roles.toSorted((a, b) => a.id - b.id);
            return {
                id: m.id,
                first_name: m.person.first_name,
                last_name: m.person.last_name,
                name: m.person.name,
                institution: m.person.institution?.name,
                legacy_credentials: m.person.legacy_credentials,
                legacy_expertise: m.legacy_expertise,
                roles: roles,
                role_priority: Math.min(...roles.map(rolePriority)),
                active: m.isActive,
                person: m.person
            }
        }).filter(m => m.active);
    });

    const defaultAdd = (obj, key) => {
        // like a python defaultdict...
        if (!obj[key]) {
            obj[key] = 0;
        }
        obj[key]++;
    }

    const counts = computed(() => {
        const counts = {};
        counts['Total'] = tableRows.value.length;
        // eslint-disable-next-line no-console
        console.log(tableRows.value);
        Object.keys(role_priorities).forEach(r => counts[r] = 0);
        tableRows.value.forEach(m => {
            m.roles.forEach(r => defaultAdd(counts, r.display_name));
            if (m.roles.length === 0) {
                defaultAdd(counts, 'None');
            }
        });
        return counts;
    });

</script>
<template>
    <div>
        <div v-for="(count, role) in counts" :key="role">
            <span>{{ role }}: {{ count }}</span>
        </div>
        <data-table
            :fields="fields"
            :data="tableRows"
            v-model:sort="tableSort"
            class="text-xs"
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

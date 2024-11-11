<script setup>
import { computed, ref, inject } from 'vue'
import { hasPermission } from '../../auth_utils';
import CredentialsView from '../people/CredentialsView.vue';
import ExpertisesView from '../people/ExpertisesView.vue';
import { formatDate } from '@/date_utils'

const fields = ref(['name', 'credentials', 'expertise', 'institution']);
if (hasPermission('ep-applications-manage')) {
    fields.value.push('coi_completed');
}


const sortDescending = ref(false); // false for ascending, true for descending

const group = inject('group');
const members = computed(() => group.value.members);

// Aggregate roles for each member
const aggregateRoles = (membersList) => {
    const aggregatedMembers = {};

    membersList.forEach(member => {
        if (!aggregatedMembers[member.id]) {
            aggregatedMembers[member.id] = {
                ...member,
                roles: [...member.roles],
            };
        } else {
            aggregatedMembers[member.id].roles = [
                ...new Set([...aggregatedMembers[member.id].roles, ...member.roles]),
            ];
        }
    });

    return Object.values(aggregatedMembers);
};

// Computed property to create table rows with aggregated roles
const rolePriority = {
    chair: 1,
    coordinator: 2,
    biocurator: 3,
    expert: 4,
};

// Function to get the highest-priority role for sorting
const getPrimaryRolePriority = (roles) => {
    for (const role of roles) {
        if (rolePriority[role.name.toLowerCase()] !== undefined) {
            return rolePriority[role.name.toLowerCase()];
        }
    }
    return Infinity; // Return a large number for roles that don't match any in the hierarchy
};

const totalMembers = computed(() => {
    return tableRows.value.length;
});

const tableRows = computed(() => {
    const allMembers = aggregateRoles(members.value);

    // Sort members by role priority, then by last name according to sortDescending
    allMembers.sort((a, b) => {
        const priorityA = getPrimaryRolePriority(a.roles);
        const priorityB = getPrimaryRolePriority(b.roles);

        // Sort by role priority first
        if (priorityA !== priorityB) {
            return priorityA - priorityB;
        }

        // If priorities are equal, sort by last name based on sortDescending
        const nameComparison = a.person.last_name.localeCompare(b.person.last_name);
        return sortDescending.value ? -nameComparison : nameComparison;
    });

    return allMembers.map(m => {
        const retVal = {
            id: m.id,
            first_name: m.person.first_name,
            last_name: m.person.last_name,
            name: m.person.name,
            institution: m.person.institution ? m.person.institution.name : null,
            legacy_credentials: m.person.legacy_credentials,
            legacy_expertise: m.legacy_expertise,
            roles: m.roles.map(role => role.name).join(', '),  // Extract role names and join them
            person: m.person
        };

        if (hasPermission('ep-applications-manage')) {
            retVal.coi_completed = formatDate(m.coi_last_completed);
        }

        return retVal;
    });
});
</script>

<template>
<div>
    <!-- Membership Total Display -->
    <div class="membership-total">
        <p>Membership Total = {{ totalMembers }}</p>
    </div>

    <!-- Table Display with Flexbox -->
    <div class="table-container">
        <table class="flex-table">
            <thead>
                <tr class="text-sm">
                    <th class="flex-column" @click="sortDescending = !sortDescending" style="cursor: pointer; display: flex; align-items: center;">
                        Name
                        <span v-if="sortDescending">▼</span>
                        <span v-else>▲</span>
                    </th>
                    <th class="flex-column">Roles</th>
                    <th class="flex-column">Credentials</th>
                    <th class="flex-column">Expertise</th>
                    <th class="flex-column">Institution</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <tr v-for="m in tableRows" :key="m.id">
                    <td class="flex-column">{{ m.name }}</td>
                    <td class="flex-column">{{ m.roles }}</td>
                    <td class="flex-column">
                        <CredentialsView :person="m.person" />
                    </td>
                    <td class="flex-column">
                        <ExpertisesView :person="m.person" :legacyExpertise="m.legacy_expertise" />
                    </td>
                    <td class="flex-column">{{ m.institution }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</template>

<style>
/* Flexbox table layout */
.table-container {
    display: flex;
    width: 100%;
}

.flex-table {
    width: 100%;
    display: flex;
    flex-direction: column;
}

.flex-column {
    flex: 1;
    padding: 8px;
}

.flex-table thead tr {
    display: flex;
    justify-content: space-between;
}

.flex-table tbody tr {
    display: flex;
    justify-content: space-between;
}
</style>

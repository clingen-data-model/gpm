<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import CredentialsView from '../people/CredentialsView.vue';
import ExpertisesView from '../people/ExpertisesView.vue';

const props = defineProps({
    members: { required: true, type: Array },
    type: { required: true, type: String }
});

// Initial debug log to verify if props.members is populated and the group type
console.log('Initial props.members:', props.members);
console.log('Initial group type:', props.type);

const fields = ref(['name', 'roles', 'credentials', 'expertise', 'institution']);
const sortDescending = ref(false);
const members = ref([]);

// Lifecycle hook to check props.members when the component mounts
onMounted(() => {
    console.log(`On mounted: Group type is ${props.type} and initial members:`, props.members);
});

// Watch for changes in props.members and update local members array
watch(
    () => props.members,
    (newMembers) => {
        console.log('Group type:', props.type); // Log the group type to check if non-VCEP groups are handled differently
        console.log('Received members in watch:', newMembers); // Debug log to check if members are received

        if (newMembers && newMembers.length > 0) {
            members.value = [...newMembers];
            console.log('Updated members ref for group type:', props.type, members.value); // Confirm members ref is updated
        } else {
            members.value = [];
            console.log(`No members found for group type: ${props.type}`);
        }
    },
    { immediate: true }
);

// Role priority mapping
const rolePriority = {
    chair: 1,
    coordinator: 2,
    biocurator: 3,
    expert: 4
};

// Function to get the primary role priority
const getPrimaryRolePriority = (roles) => {
    for (const role of roles) {
        const roleName = role.name.toLowerCase();
        if (rolePriority[roleName] !== undefined) {
            return rolePriority[roleName];
        }
    }
    return Infinity;
};

// Aggregate roles for unique members
const aggregateRoles = (membersList) => {
    const aggregatedMembers = {};

    membersList.forEach((member) => {
        if (!aggregatedMembers[member.id]) {
            aggregatedMembers[member.id] = { ...member, roles: [...member.roles] };
        } else {
            aggregatedMembers[member.id].roles = [
                ...new Set([...aggregatedMembers[member.id].roles, ...member.roles])
            ];
        }
    });

    const result = Object.values(aggregatedMembers);
    console.log('Aggregated members for group type:', props.type, result); // Debug log to ensure aggregation is correct
    return result;
};

// Computed total members count based on processed table rows
const totalMembers = computed(() => {
    console.log('Total members in tableRows for group type:', props.type, tableRows.value.length); // Debug log
    return tableRows.value.length;
});

// Computed table rows with sorting and aggregation
const tableRows = computed(() => {
    const allMembers = aggregateRoles(members.value);

    console.log('All aggregated members in tableRows for group type:', props.type, allMembers); // Log to ensure allMembers contains data

    allMembers.sort((a, b) => {
        const priorityA = getPrimaryRolePriority(a.roles);
        const priorityB = getPrimaryRolePriority(b.roles);

        if (priorityA !== priorityB) return priorityA - priorityB;

        const nameComparison = (a.person?.last_name || '').localeCompare(b.person?.last_name || '');
        return sortDescending.value ? -nameComparison : nameComparison;
    });

    return allMembers.map((m) => ({
        id: m.id,
        name: m.person?.name,
        roles: m.roles.map((role) => role.name).join(', '),
        credentials: m.person?.legacy_credentials,
        expertise: m.legacy_expertise,
        institution: m.person?.institution?.name || null,
        person: m.person
    }));
});
</script>

<template>
    <div>
        <!-- Membership Total Display -->
        <div class="membership-total">
            <p>Membership Total = {{ totalMembers }}</p>
        </div>

        <!-- Table Display -->
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
                    <tr v-if="tableRows.length === 0">
                        <td colspan="5" class="flex-column">No members found.</td>
                    </tr>
                    <tr v-for="m in tableRows" :key="m.id">
                        <td class="flex-column">{{ m.name }}</td>
                        <td class="flex-column">{{ m.roles }}</td>
                        <td class="flex-column">
                            <CredentialsView :person="m.person" />
                        </td>
                        <td class="flex-column">
                            <ExpertisesView :person="m.person" :legacyExpertise="m.expertise" />
                        </td>
                        <td class="flex-column">{{ m.institution }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- VCEP-specific content: Scope and Draft Specifications -->
        <div v-if="type === 'VCEP'">
            <p>Scope content specific to VCEP displayed here...</p>
            <p>Draft Specifications content for VCEP displayed here...</p>
        </div>

        <!-- Message for non-VCEP -->
        <div v-else>
            <p>Scope available, but Draft Specifications are only for VCEPs.</p>
        </div>
    </div>
</template>

<style>
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

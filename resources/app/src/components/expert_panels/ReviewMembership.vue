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

    const fields = ref(['name', 'credentials', 'expertise', 'institution']);
    if (hasPermission('ep-applications-manage')) {
        fields.value.push('coi_completed');
    }

    const members = ref([]);
    const chairs = computed(() => tableRows.value.filter(m => m.roles.includes('chair')));
    const experts = computed(() => tableRows.value.filter(m => m.roles.includes('expert')));

    const memberGroups = computed(() => [
        {
            title: 'Leadership',
            members: chairs.value
        },
        {
            title: 'Coordination',
            members: tableRows.value.filter(m => m.roles.includes('coordinator'))
        },
        {
            title: 'Biocuration',
            members: tableRows.value.filter(m => m.roles.includes('biocurator'))
        },
        {
            title: 'Expertise',
            members: experts.value
        }
    ])

    watch(() => props.members, async to => {
        if (!to) {
            return;
        }

        members.value = [...to];

        members.value.sort((a, b) => {
            if (a.roles.includes('chair') && !b.roles.includes('chair')) {
                return -1;
            }
            if (!a.roles.includes('chair') && b.roles.includes('chair')) {
                return 1;
            }

            if (a.roles.includes('expert') && !b.roles.includes('expert')) {
                return -1;
            }
            if (!a.roles.includes('expert') && b.roles.includes('expert')) {
                return 1;
            }

            return 0;
        })
    }, {immediate: true})

    const tableRows = computed( () => {
        return props.members.map(m => {
            const retVal = {
                id: m.id,
                first_name: m.person.first_name,
                last_name: m.person.last_name,
                name: m.person.name,
                institution: m.person.institution ? m.person.institution.name : null,
                // credentials: m.person.credentials,
                legacy_credentials: m.person.legacy_credentials,
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
        <table>
            <template v-for="g in memberGroups" :key="g.title">
                <thead>
                    <tr>
                        <th colspan="6" class="bg-white border-0 pl-0 pb-1 pt-3">
                            <span class="text-lg">{{g.title}}</span>
                            &nbsp;
                            <badge size="xxs">{{g.members.length}}</badge>
                        </th>
                    </tr>
                </thead>
                <template v-if="g.members.length > 0">
                    <thead>
                        <tr class="text-sm">
                            <th>Name</th>
                            <th>Credentials</th>
                            <th>Expertise</th>
                            <th>Institution</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <tr v-for="m in g.members" :key="m.id">
                            <td>{{m.person.name}}</td>
                            <td>
                                <CredentialsView :person="m.person" />
                            </td>
                            <td>
                                <ExpertisesView :person="m.person" :legacyExpertise="m.legacy_expertise" />
                            </td>
                            <td>{{m.institution}}</td>
                        </tr>
                    </tbody>
                </template>
            </template>
        </table>
    </div>
</template>

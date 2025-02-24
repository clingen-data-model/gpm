<script>
import {hasPermission, userIsPerson} from '@/auth_utils.js'
import CoiDetail from '@/components/applications/CoiDetail.vue';
import Person from '@/domain/person'
import {kebabCase} from '@/string_utils.js'
import {computed, ref} from 'vue';

export default {
    name: 'CoiList',
    components: {
        CoiDetail
    },
    props: {
        person: {
            type: Person,
            required: true
        }
    },
    setup (props) {

        const cois = computed(() => {
            return props.person.membershipsWithCompletedCois
                .map(m => {
                    return m.cois.map(coi => {
                        coi.group = m.group;
                        return coi;
                    })
                })
                .filter(coi => coi.completed_at !== null)
                .flat()
        });
        const needsCoi = computed(() => {
            if (!props.person.memberships) {
                return false;
            }
            return props.person.memberships
                    .filter(m => {
                        if (m.cois === null || m.cois.length === 0) {
                            if (m.group.expert_panel) {
                                return true
                            }
                        }
                        return false;
                    });
        });

        const coiFields = [
            {
                name: 'group.name',
                label: 'Group',
                type: String,
                sortable: true,
                resolveValue: item => item.group.display_name
            },
            {
                name: 'latest_coi.version',
                label: 'Version',
                type: String,
                sortable: true
            },
            {
                name: 'coi_last_completed',
                label: 'Completed',
                sortable: true,
                type: Date,
            },
        ];
        const fields = computed(() => {
            const actionField = {
                name: 'actions',
                sortable: false,
            };

            const idField ={
                name: 'latest_coi.id',
                label: 'ID',
                sortable: true
            }

            const fields = [...coiFields];

            if (hasPermission('people-manage')) {
                return  [idField, ...fields, actionField];
            }

            if (userIsPerson(props.person)) {
                return [...fields, actionField];
            }

            return coiFields;
        })

        const coiSort = ref({
            field: 'group.name',
            desc: false
        })

        const getCoiRoute = (membership) => {
            return {
                name: 'alt-coi',
                params: {
                    name: kebabCase(membership.group.name),
                    code: membership.group.coi_code
                }
            }
        }
        const currentCoi = ref(null);
        const currentGroup = ref(null);
        const showResponseDialog = ref(false);

        const showCoiResponse = (membership) => {
            const latestCoi = membership.cois[membership.cois.length - 1];
            currentCoi.value = latestCoi;
            currentGroup.value = membership.group;
            showResponseDialog.value = true;
        }

        return {
            needsCoi,
            cois,
            coiFields,
            coiSort,
            currentCoi,
            currentGroup,
            showResponseDialog,
            showCoiResponse,
            getCoiRoute,
            fields,
        }
    }
}
</script>
<template>
    <div>
        <div v-if="person.membershipsWithCoiRequirement.length > 0">
            <div v-if="person.hasPendingCois" class="mb-4">
                <div v-if="userIsPerson(person)">
                    <h3>You must complete a Conflict of Interest Disclosure for the following memberships:</h3>
                    <div class="my-2">
                        <router-link
                            v-for="membership in person.membershipsWithPendingCois"
                            :key="membership.id"
                            class="block my-0 font-bold p-2 border border-gray-300 first:rounded-t-lg last:rounded-b-lg cursor-pointer hover:bg-blue-50 link"
                            :to="getCoiRoute(membership)"
                        >
                            {{membership.group.display_name}}
                        </router-link>
                    </div>
                </div>
                <div v-else>
                    <h3>Outstanding COI disclosures:</h3>
                    <ul>
                        <li
                            class="list-disc ml-6"
                            v-for="membership in person.membershipsWithPendingCois"
                            :key="membership.id"
                        >
                            {{membership.group.display_name}}
                        </li>
                    </ul>
                </div>
            </div>
            <section v-if="person.hasCompletedCois">
                <h3>Completed &amp; Current COI Disclosures</h3>
                <data-table
                    :fields="fields"
                    :data="person.membershipsWithCompletedCois"
                    v-model:sort="coiSort"
                    class="my-2"
                >
                    <template #cell-actions="{item}">
                        <div v-if="item.coi_last_completed">
                            <button
                                class="btn btn-xs"
                                @click="showCoiResponse(item)"
                            >
                                View response
                            </button>
                            &nbsp;
                            <router-link
                                v-if="userIsPerson(person)"
                                :to="{
                                    name: 'alt-coi',
                                    params: {code: item.group.coi_code, name: kebabCase(item.group.name)}
                                }"
                                class="btn btn-xs"
                            >Update COI</router-link>
                        </div>
                    </template>
                </data-table>
            </section>
            <section v-if="person.hasOutdatedCois">
                <h3>Past COI Disclosures</h3>
                <data-table
                    :fields="fields"
                    :data="person.membershipsWithOutdatedCois"
                    v-model:sort="coiSort"
                    class="my-2"
                >
                    <template #cell-actions="{item}">
                        <div v-if="item.coi_last_completed">
                            <button
                                class="btn btn-xs"
                                @click="showCoiResponse(item)"
                            >View response</button>
                            &nbsp;
                            <router-link
                                v-if="userIsPerson(person)"
                                :to="{
                                    name: 'alt-coi',
                                    params: {code: item.group.coi_code, name: kebabCase(item.group.name)}
                                }"
                                class="btn btn-xs"
                            >Update COI</router-link>
                        </div>
                    </template>
                </data-table>
            </section>
            <div v-if="!person.hasPending && !person.completedCois" class="well">
                None of your memberships require a conflict of interest disclosure
            </div>
        </div>
        <div class="well" v-else>You are not required to complete conflict of interest disclsoures.</div>
        <teleport to="body">
            <modal-dialog v-model="showResponseDialog" size="xl">
                <CoiDetail :coi="currentCoi" :group="currentGroup" v-if="currentCoi"></CoiDetail>
            </modal-dialog>
        </teleport>
    </div>
</template>

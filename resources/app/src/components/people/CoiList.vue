<template>
    <div>
        <div v-if="person.memberships.length > 0">
            <div v-if="needsCoi.length > 0">
                <h3>You must complete a Conflict of Interest Disclosure for the following memberships:</h3>
                <div class="my-2">
                    <router-link 
                        v-for="membership in needsCoi" 
                        :key="membership.id" 
                        class="block my-0 font-bold p-2 border border-gray-300 first:rounded-t-lg last:rounded-b-lg cursor-pointer hover:bg-blue-50 link"
                        :to="getCoiRoute(membership)" 
                    >
                        {{membership.group.name}}
                    </router-link>
                </div>
            </div>
            <h3>Completed Conflict of Interest Disclosures</h3>
            <data-table 
                :fields="fields" 
                :data="cois" 
                v-model:sort="coiSort"
                v-if="cois.length > 0"
                class="my-2"
            >
                <template v-slot:cell-actions="{item}">
                    <div v-if="item.completed_at">
                        <button 
                            class="btn btn-xs" 
                            @click="showCoiResponse(item)" 
                        >View response</button>
                        &nbsp;
                        <router-link 
                            :to="{
                                name: 'alt-coi', 
                                params: {code: item.group.expert_panel.coi_code, name: this.kebabCase(item.group.name)}
                            }" 
                            class="btn btn-xs"
                        >Update COI</router-link>
                    </div>
                </template>
            </data-table>
            <div v-if="needsCoi.length == 0 && cois.length == 0" class="well">
                None of your memberships require a conflict of interest disclosure
            </div>
        </div>
        <div class="well" v-else>You are not required to complet conflict of interest disclsoure</div>
        <teleport to="body">
            <modal-dialog v-model="showResponseDialog" size="xl">
                <coi-detail :coi="currentCoi" v-if="currentCoi"></coi-detail>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import {ref, computed} from 'vue';
import CoiDetail from '@/components/applications/CoiDetail';
import Person from '@/domain/person'
import {kebabCase} from '@/utils'
import {hasPermission, userIsPerson} from '@/auth_utils'

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
            return props.person.memberships
                .filter(m => m.cois !== null && m.cois.length > 0)
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
            return props.person.memberships
                    .filter(m => (m.cois === null || m.cois.length === 0) && m.group.expert_panel);
        });
        const coiFields = [
            {
                name: 'group.name',
                label: 'Group',
                type: String,
                sortable: true
            },
            {
                name: 'completed_at',
                label: 'Completed',
                sortable: false,
                type: Date,
            },
        ];
        const fields = computed(() => {
            const actionField = {
                name: 'actions',
                sortable: false,
            };

            const idField ={
                name: 'id',
                label: 'ID',
                sortable: true
            }
            
            let fields = [...coiFields];

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
                    code: membership.group.expert_panel.coi_code
                }
            }
        }
        const currentCoi = ref(null);
        const showResponseDialog = ref(false);
        const showCoiResponse = (coi) => {
            currentCoi.value = coi;
            showResponseDialog.value = true;
        }

        return {
            needsCoi,
            cois,
            coiFields,
            coiSort,
            currentCoi,
            showResponseDialog,
            showCoiResponse,
            getCoiRoute,
            fields,
        }
    }
}
</script>
<template>
    <div>
        <h1>
            Dashboard
            <div class="note font-normal">
                User ID: {{user.id}} | Person ID: {{user.person ? user.person.id : 'no person!!'}}
            </div>
        </h1>

        <NotificationList :user="user" />
        <DashboardAlerts :user="user" />
        <ApplicationActivity :user="user"
            v-if="hasAnyPermission(['ep-applications-manage', 'ep-applications-comment', 'ep-applications-approve'])" 
            class="py-4 border-b"
        />
        <tabs-container class="mt-8">
            <tab-item label="Your Groups">
                <div class="well" v-if="!groups.length">You are not assigned to any groups.</div>
                <data-table
                    v-else
                    :data="groups"
                    :fields="groupFields"
                    v-model:sort="groupSort"
                    @rowClick="navigateToGroup"
                    row-class="cursor-pointer"
                >
                    <template v-slot:cell-status_name="{value}">
                        <badge :color="groupBadgeColor(value)">{{value}}</badge>
                    </template>
                    <template v-slot:cell-displayName="{item}">
                        {{item.name}} {{item.type.name.toUpperCase()}}
                    </template>
                </data-table>
            </tab-item>

            <tab-item label="Your Info">
                <person-profile :person="personFromStore"></person-profile>
            </tab-item>

            <tab-item label="COIs">
                <coi-list :person="user.person"></coi-list>
            </tab-item>

        </tabs-container>
    </div>
</template>
<script>
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
import {ref, computed, onMounted, watch} from 'vue'
import CoiList from '@/components/people/CoiList.vue'
import PersonProfile from '@/components/people/PersonProfile.vue'
import Person from "@/domain/person"
import Group from "@/domain/group"
import configs from '@/configs'
import ApplicationActivity from '../components/dashboard/ApplicationActivity.vue';
import DashboardAlerts from '@/components/dashboard/DashboardAlerts.vue'
import NotificationList from '../components/NotificationList.vue'

export default {
    name: 'Dashboard',
    components: {
        CoiList,
        PersonProfile,
        ApplicationActivity,
        DashboardAlerts,
        NotificationList,
    },
    data() {
        return {
            testInst: null
        }
    },
    props: {
        
    },
    setup () {
        const store = useStore();
        const router = useRouter();
        const user = computed(() => {
            return store.getters['currentUser']
        });
        const personFromStore = computed(() => {
            return store.getters['people/currentItem'] || new Person();
        })
        const loadPersonInStore = () => {
            if (user.value.id && user.value.person && user.value.person.id) {
                store.commit('people/addItem', user.value.person);
                store.commit('people/setCurrentItemIndex', user.value.person);
            }
        }
        watch(() => user, () => {
            loadPersonInStore();
        });
        onMounted(() => {
            loadPersonInStore();
        });

        // GROUPS
        // TODO: Get groups by search with TONS of info.
        // TODO: Extract that work to a module.
        const groups = computed(() => {
            return user.value.memberships
                    .map(m => m.group)
                    .filter(g => g !== null)
                    .map(group => new Group(group))
        });

        const groupFields = ref([
            {
                name: 'displayName',
                sortable: true,
                type: String
            },
            {
                name: 'status.name',
                label: 'Status',
                sortable: true,
                resolveValue: (item) => {
                    if (item.status.id == configs.groups.statuses['applying'].id) {
                        return item.status.name+' - '+item.expert_panel.currentStepName;
                    }
                    return item.status.name;
                },
                type: String
            },
        ]);
        const groupSort = ref({
            field: 'displayName',
            desc: false
        });
        const groupBadgeColor = (status) => {
            const map = {
                Active: 'green',
                Applying: 'blue',
                Retired: 'yellow',
                Removed: 'red'
            }
            return map[status] || 'blue'
        };

        // TODO: extract to module.
        // TODO: Get coi data on demand

        onMounted(async () => {
            await store.dispatch('forceGetCurrentUser');
        })

        return {
            user,
            personFromStore,
            groups,
            groupSort,
            groupFields,
            groupBadgeColor,
            navigateToGroup: (item) => {
                router.push({
                    name: 'GroupDetail',
                    params: {uuid: item.uuid}
                })
            }
        }
        
    }
}
</script>
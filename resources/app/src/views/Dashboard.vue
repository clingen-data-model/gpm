<template>
    <div>
        <h1>Dashboard</h1>
        <transition-group tag="div" name="slide-fade-down">
            <notification-item 
                v-for="notification in notifications" :key="notification.id"
                :notification="notification"
                class="mt-2"
                @removeClicked="removeNotification(notification)"
            ></notification-item>
        </transition-group>
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
                </data-table>
            </tab-item>

            <tab-item label="COIs">
                <div v-if="user.memberships.length > 0">
                    <div v-if="needsCoi.length > 0">
                        <h3>You must complete a Conflict of Interest Disclosure for the following memberships:</h3>
                        <div class="my-2">
                            <router-link 
                                v-for="membership in needsCoi" 
                                :key="membership.id" 
                                class="block my-0 font-bold p-2 border border-gray-300 first:rounded-t-lg last:rounded-b-lg cursor-pointer hover:bg-blue-50 link"                                :to="getCoiRoute(membership)" 
                            >{{membership.group.name}}</router-link>
                        </div>
                    </div>
                    <h3>Completed Conflict of Interest Disclosures</h3>
                    <data-table 
                        :fields="coiFields" 
                        :data="cois" 
                        v-model:sort="coiSort"
                        v-if="cois.length > 0"
                        class="my-2"
                    >
                        <template v-slot:cell-actions="{item}">
                            <div v-if="item.completed_at">
                                <button class="btn btn-xs" @click="showCoiResponse(item)">View response</button>
                                &nbsp;
                                <router-link 
                                    :to="{
                                        name: 'alt-coi', 
                                        params: {code: item.group.expert_panel.coi_code, name: kebabCase(item.group.name)}
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
            </tab-item>
        </tabs-container>
        <div class="mt-8 space-y-4">
            <collapsible title="User Memberships">
                <pre>{{user.memberships}}</pre>
            </collapsible>
            <collapsible title="User Groups">
                <pre>{{groups}}</pre>
            </collapsible>
            <collapsible title="User Cois">
                <pre>{{cois}}</pre>
            </collapsible>
            <collapsible title="needsCoi">
                <pre>{{needsCoi}}</pre>
            </collapsible>
        </div>
    </div>
</template>
<script>
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
import {ref, computed, onMounted} from 'vue'
import NotificationItem from '@/components/NotificationItem'
import {kebabCase} from '@/utils'
import CoiDetail from '@/components/applications/CoiDetail';


export default {
    name: 'Dashboard',
    components: {
        NotificationItem,
        CoiDetail
    },
    props: {
        
    },
    setup () {
        const store = useStore();
        const router = useRouter();
        const user = computed(() => {
            return store.getters['currentUser']
        });

        // NOTIFICATIONS
        // TODO: Extract to modules
        const loadingNotifications = ref(false);
        const notifications = ref([]);
        const getNotifications = async () => {
            console.log('getNotifiations...');
            loadingNotifications.value = true;
            await setTimeout(() => {
                notifications.value.push({id: 1, message: 'This is a dummy notification.'});
                notifications.value.push({id: 2, message: 'Start getting notificaitons from the server!'});
            }, 1000);
            loadingNotifications.value = false;
        }
        const removeNotification = (notification) => {
            const index = notifications.value.findIndex((item) => item.id == notification.id);
            if (index > -1) {
                notifications.value.splice(index, 1);
            }
        }


        // GROUPS
        // TODO: Get groups by search with TONS of info.
        // TODO: Extract that work to a module.
        const groups = computed(() => user.value.memberships.map(m => m.group).filter(g => g !== null));
        console.log(groups.value);
        const groupFields = ref([
            {
                name: 'name',
                sortable: true,
                type: String
            },
            {
                name: 'status.name',
                label: 'Status',
                sortable: true,
                type: String
            },
            {
                name: 'info',
                sortable: false,
                type: String
            }
        ]);
        const groupSort = ref({
            field: 'name',
            desc: false
        });
        const groupBadgeColor = (status) => {
            const map = {
                Active: 'green',
                'Pending-Approval': 'blue',
                Retired: 'yellow',
                Removed: 'red'
            }
            return map[status] || 'blue'
        };


        // TODO: extract to module.
        // TODO: Get coi data on demand
        const cois = computed(() => {
            return user.value.memberships
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
            console.log(user.value.memberships);
            return user.value.memberships
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
            {
                name: 'actions',
                sortable: false,
            }
        ];
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


        onMounted(() => {
            getNotifications();
        })

        return {
            user,
            loadingNotifications,
            notifications,
            groups,
            groupSort,
            groupFields,
            groupBadgeColor,
            needsCoi,
            cois,
            coiFields,
            coiSort,
            currentCoi,
            showResponseDialog,
            showCoiResponse,
            getNotifications,
            removeNotification,
            navigateToGroup: (item) => {
                console.log(item);
                router.push({
                    name: 'GroupDetail',
                    params: {uuid: item.uuid}
                })
            },
            getCoiRoute,
            kebabCase,
        }
        
    }
}
</script>
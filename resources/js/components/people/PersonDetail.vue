<script>
import { fetchEntries, logEntries } from "@/adapters/log_entry_repository";
import ActivityLog from "@/components/log_entries/ActivityLog.vue";
import CoiList from '@/components/people/CoiList.vue'

import DemographicsForm from '@/components/people/DemographicsForm.vue';
import PersonMailLog from "@/components/people/PersonMailLog.vue";
import PersonMergeForm from '@/components/people/PersonMergeForm.vue'
import PersonProfile from '@/components/people/PersonProfile.vue'
import ProfilePicture from "@/components/people/ProfilePicture.vue";
import { formatDateTime as formatDate } from '@/date_utils'
import { mapGetters } from 'vuex'
import TabsContainer from '../TabsContainer.vue'
import MembershipList from './MembershipList.vue'


export default {
    name: 'PersonDetail',
    components: {
        TabsContainer,
        MembershipList,
        PersonProfile,
        DemographicsForm,
        PersonMergeForm,
        CoiList,
        ActivityLog,
        PersonMailLog,
        ProfilePicture,
    },
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    data() {
        return {
            showDeleteConfirmation: false,
            showMergeForm: false,
            mailLoading: false,
            logsLoading: false,
        }
    },
    watch: {
        uuid: {
            immediate: true,
            async handler () {
                await this.$store.dispatch('people/getPerson', { uuid: this.uuid });
                if (this.coordinatesPerson(this.person)) {
                    this.getLogEntries();
                    this.getMailLog();
                }
            }
        },
        'person.memberships': function (to) {
            if (to.length > 0) {
                this.getLogEntries();
                this.getMailLog();
            }
        }
    },
    computed: {
        ...mapGetters({
            person: 'people/currentItem'
        }),
        showModal: {
            get() {
                return this.$route.meta.showModal
            },
            set(value) {
                if (value) {
                    this.$router.push({ name: 'PersonEdit', params: { uuid: this.person.uuid } })
                }
                this.$router.push({ name: 'PersonDetail', params: { uuid: this.person.uuid } })
            }
        },
        sortedMailLog() {
            return [...this.person.mailLog].sort((a, b) => {
                if (a.created_at === b.created_at) {
                    return 0;
                }
                return (Date.parse(a.created_at) > Date.parse(b.created_at))
                    ? -1 : 1;
            })
        },
    },
    methods: {
        initDelete() {
            this.showDeleteConfirmation = true;
        },
        async commitDelete() {
            await this.$store.dispatch('people/deletePerson', this.person);
            this.showDeleteConfirmation = false;
            this.$router.go(-1);
        },
        cancelDelete() {
            this.showDeleteConfirmation = false;
        },
        initMerge() {
            this.showMergeForm = true;
        },
        handleMerged() {
            this.showMergeForm = false;
            this.$router.go(-1);
        },
        handleMergeCanceled() {
            this.showMergeForm = false;
        },
        async getMailLog() {
            this.mailLoading = true;
            await this.$store.dispatch('people/getMail', this.person);
            this.mailLoading = false;
        },
        async getLogEntries() {
            this.logsLoading = true;
            await this.fetchEntries(`/api/people/${this.uuid}/activity-logs`);
            this.logsLoading = false;
        }
    },
    setup() {
        return {
            formatDate,
            logEntries,
            fetchEntries
        }
    },
    onmounted() {
        this.$store.dispatch('people/clearCurrentItem');
    }
}
</script>
<template>
    <div>
        <header class="pb-4">
            <div class="flex space-x-4">

                <ProfilePicture :person="person" style="width: 155px" class="rounded-lg" />

                <div class="flex-1">
                    <router-link class="note" to="/people">People</router-link>
                    <h1 class="flex justify-between items-center">
                        <div>
                            {{ person.name }}
                            <note>ID: {{ person.id }}</note>
                        </div>
                        <router-link :to="`/people/${uuid}/edit`" class="btn btn-xs flex-grow-0"
                            v-if="(hasPermission('people-manage') || userIsPerson(person)) || coordinatesPerson(person)">
                            <icon-edit width="16" heigh="16" />
                        </router-link>

                    </h1>
                    <dictionary-row label="Email" label-class="w-24 font-bold">
                        {{ person.email }}
                    </dictionary-row>
                    <dictionary-row label="Institution" label-class="w-24 font-bold">
                        {{ person.institution ? person.institution.name : null }}
                    </dictionary-row>
                </div>
            </div>
        </header>
        <TabsContainer>
            <tab-item label="groups">
                <h2>Groups</h2>
                <MembershipList :person="person"></MembershipList>
            </tab-item>
            <tab-item label="Info">
                <h2>Profile</h2>
                <PersonProfile :person="person"></PersonProfile>
            </tab-item>

            <tab-item label="Demographics" :visible="(hasRole('super-admin') || userIsPerson(person))">
                <DemographicsForm :person="person"></DemographicsForm>

            </tab-item>

            <tab-item label="Conflict of Interest">
                <CoiList :person="person"></CoiList>
            </tab-item>
            <!-- <tab-item label="Documents">
                docs
            </tab-item> -->
            <!-- <tab-item label="Training &amp; Attestations">
            </tab-item> -->

            <tab-item label="Email Log"
                :visible="hasPermission('people-manage') || userIsPerson(person) || coordinatesPerson(person)">
                <header class="flex space-x-4 items-center">
                    <h2>Mail sent to {{ person.first_name }}</h2>
                    <refresh-button :loading="mailLoading" @click="getMailLog" />
                </header>
                <PersonMailLog :person="person" :mail="sortedMailLog"></PersonMailLog>
            </tab-item>

            <tab-item label="Log" :visible="hasPermission('people-manage') || coordinatesPerson(person)">
                <div class="flex space-x-4 items-center">
                    <h2>Log Entries</h2>
                    <refresh-button :loading="logsLoading" @click="getLogEntries" />
                </div>
                <ActivityLog :log-entries="logEntries" :api-url="`/api/people/${person.uuid}/activity-logs`"
                    :log-updated="getLogEntries"></ActivityLog>
            </tab-item>

            <tab-item label="Admin" :visible="hasPermission('people-manage')">
                <section class="border my-4 p-4 bg-red-100 border-red-200 rounded">
                    <h2 class="mb-4 text-red-800">
                        Here be dragons. Proceed with caution.
                    </h2>
                    <p><button class="btn btn red" @click="initMerge">Merge Person into another</button></p>
                    <p><button class="btn btn red" @click="initDelete">Delete Person</button></p>
                </section>
            </tab-item>

        </TabsContainer>

        <teleport to="body">
            <modal-dialog v-model="showModal" :title="$route.meta.title">
                <router-view name="modal"></router-view>
            </modal-dialog>
            <modal-dialog :title="`You are about to delete ${person.name}`" v-model="showDeleteConfirmation">
                <p>You are about to delete this person. All related data will also be deleted including:</p>
                <ul class="list-disc pl-6">
                    <li>User record, system roles, and system permissions (if account is activated)</li>
                    <li>Invite</li>
                    <li>Group Memberships and group roles (if any)</li>
                </ul>
                <div class="border my-4 px-2 py-1 font-bold bg-red-100 border-red-200 rounded text-red-800">
                    This cannot be undone.
                </div>
                <button-row :submit-text="`Delete ${person.name}`" @submitted="commitDelete" @canceled="cancelDelete"
                    submit-variant="red" />
            </modal-dialog>
            <modal-dialog :title="`Merge ${person.name} into another person`" v-model="showMergeForm">
                <PersonMergeForm :obsolete="person" @saved="handleMerged"
                    @canceled="handleMergeCanceled"></PersonMergeForm>
            </modal-dialog>
        </teleport>
    </div>
</template>
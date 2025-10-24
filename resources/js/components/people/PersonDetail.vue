<script>
import { mapGetters } from 'vuex'
import { formatDateTime as formatDate } from '@/date_utils'
import { logEntries, fetchEntries, saveEntry } from "@/adapters/log_entry_repository";

import TabsContainer from '../TabsContainer.vue'
import MembershipList from './MembershipList.vue'
import PersonProfile from '@/components/people/PersonProfile.vue'
import PersonMergeForm from '@/components/people/PersonMergeForm.vue'
import CoiList from '@/components/people/CoiList.vue'
import ActivityLog from "@/components/log_entries/ActivityLog.vue";
import PersonMailLog from "@/components/people/PersonMailLog.vue";
import ProfilePicture from "@/components/people/ProfilePicture.vue";
import DemographicsForm from '@/components/people/DemographicsForm.vue';


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
            showRetireAllConfirmation: false,
            retireAlsoDisableLogin: false,
            retireReason: '',
            retireAllBusy: false,
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
        initRetireAll() { this.showRetireAllConfirmation = true },
        cancelRetireAll() { this.showRetireAllConfirmation = false },
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
        },
        async commitRetireAll() {
          try {
            this.retireAllBusy = true
            const data = await this.saveEntry(`/api/people/${this.person.uuid}/retire`, {
              disable_login: this.retireAlsoDisableLogin,
              reason: this.retireReason || null
            })
            this.showRetireAllConfirmation = false
            const n = Number(data?.memberships_retired ?? 0)
            const parts = [`Retired from ${n} ${n === 1 ? 'group' : 'groups'}.`]
            if (data?.disable_login) parts.push('Login has been disabled for this member.')

            await this.$store.dispatch('people/getPerson', { uuid: this.uuid })
            this.getLogEntries()
            this.$store.commit('pushSuccess', parts.join(' '))
          } catch (e) {
            this.$store.commit('pushError', 'Failed to retire user — see console/logs.')
          } finally {
            this.retireAllBusy = false
          }
        },
    },
    setup() {
        return {
            formatDate,
            logEntries,
            fetchEntries,
            saveEntry,
        }
    },
    mounted() {
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
          <router-link class="note" to="/people">
            People
          </router-link>
          <h1 class="flex justify-between items-center">
            <div>
              {{ person.name }}
              <note>ID: {{ person.id }}</note>
            </div>
            <router-link
              v-if="(hasPermission('people-manage') || userIsPerson(person)) || coordinatesPerson(person)" :to="`/people/${uuid}/edit`"
              class="btn btn-xs flex-grow-0"
            >
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
        <MembershipList :person="person" />
      </tab-item>
      <tab-item label="Info">
        <h2>Profile</h2>
        <PersonProfile :person="person" />
      </tab-item>

      <tab-item label="Demographics" :visible="(hasRole('super-admin') || userIsPerson(person))">
        <DemographicsForm :person="person" />
      </tab-item>

      <tab-item label="Conflict of Interest">
        <CoiList :person="person" />
      </tab-item>
      <!-- <tab-item label="Documents">
                docs
            </tab-item> -->
      <!-- <tab-item label="Training &amp; Attestations">
            </tab-item> -->

      <tab-item
        label="Email Log"
        :visible="hasPermission('people-manage') || userIsPerson(person) || coordinatesPerson(person)"
      >
        <header class="flex space-x-4 items-center">
          <h2>Mail sent to {{ person.first_name }}</h2>
          <refresh-button :loading="mailLoading" @click="getMailLog" />
        </header>
        <PersonMailLog :person="person" :mail="sortedMailLog" />
      </tab-item>

      <tab-item label="Log" :visible="hasPermission('people-manage') || coordinatesPerson(person)">
        <div class="flex space-x-4 items-center">
          <h2>Log Entries</h2>
          <refresh-button :loading="logsLoading" @click="getLogEntries" />
        </div>
        <ActivityLog
          :log-entries="logEntries" :api-url="`/api/people/${person.uuid}/activity-logs`"
          :log-updated="getLogEntries"
        />
      </tab-item>

      <tab-item label="Admin" :visible="hasPermission('people-manage')">
        <section class="border my-4 p-4 bg-red-100 border-red-200 rounded">
          <h2 class="mb-4 text-red-800">
            Here be dragons. Proceed with caution.
          </h2>
          <p>
            <button class="btn btn red" @click="initMerge">
              Merge Person into another
            </button>
          </p>
          <p>
            <button class="btn btn red" @click="initDelete">
              Delete Person
            </button>
          </p>
          <p>&nbsp;</p>
          <p>
            This button will remove the person from all the groups they are a member of.<br />
            <button class="btn btn red" @click="initRetireAll">              
              Retire Person
            </button>
          </p>
        </section>
      </tab-item>
    </TabsContainer>

    <teleport to="body">
      <modal-dialog v-model="showModal" :title="$route.meta.title">
        <router-view name="modal" />
      </modal-dialog>
      <modal-dialog v-model="showDeleteConfirmation" :title="`You are about to delete ${person.name}`">
        <p>You are about to delete this person. All related data will also be deleted including:</p>
        <ul class="list-disc pl-6">
          <li>User record, system roles, and system permissions (if account is activated)</li>
          <li>Invite</li>
          <li>Group Memberships and group roles (if any)</li>
        </ul>
        <div class="border my-4 px-2 py-1 font-bold bg-red-100 border-red-200 rounded text-red-800">
          This cannot be undone.
        </div>
        <button-row
          :submit-text="`Delete ${person.name}`" submit-variant="red" @submitted="commitDelete"
          @canceled="cancelDelete"
        />
      </modal-dialog>
      <modal-dialog v-model="showMergeForm" :title="`Merge ${person.name} into another person`">
        <PersonMergeForm
          :obsolete="person" @saved="handleMerged"
          @canceled="handleMergeCanceled"
        />
      </modal-dialog>
      <modal-dialog v-model="showRetireAllConfirmation" :title="`Retire ${person.name} from all groups and disable account?`">
        <p>This will:</p>
        <ul class="list-disc pl-6">
          <li>End all active group memberships</li>
          <li>Mark the person as retired</li>
          <li>Optionally disable login/revoke tokens</li>
        </ul>

        <div class="mt-3">
          <label class="flex items-start space-x-2">
            <input type="checkbox" v-model="retireAlsoDisableLogin" class="mt-1" />
            <span>
              Also disable login for this user.<br />
              <small class="text-gray-500">
                This will delete the user's account data but keep their member record for reference.
                Contact an administrator if you need to restore their access later.
              </small>
            </span>
          </label>
        </div>

        <div class="mt-3">
          <label class="block font-semibold mb-1">Reason (optional)</label>
          <textarea v-model="retireReason" class="w-full border rounded p-2" rows="3" placeholder="e.g., Left ClinGen"></textarea>
        </div>

        <div class="border my-4 px-2 py-1 font-bold bg-red-100 border-red-200 rounded text-red-800">
          This cannot be undone.
        </div>

        <button-row :submit-text="`Retire ${person.name}`" submit-variant="red" :busy="retireAllBusy" @submitted="commitRetireAll" @canceled="cancelRetireAll"/>
      </modal-dialog>
    </teleport>
  </div>
</template>
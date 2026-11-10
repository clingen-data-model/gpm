<script>
import api from '@/http/api'
import { formatDateTime } from '@/date_utils'
import { cilogonEnabled, cilogonErrorMessage } from '@/domain/cilogon'
import CILogonProviderPicker from '@/components/auth/CILogonProviderPicker.vue'

export default {
    name: 'SignInMethods',
    components: {
        CILogonProviderPicker
    },
    props: {
        person: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            cilogonEnabled,
            identities: [],
            loading: false,
            pendingRemoval: null,
        }
    },
    computed: {
        linkError() {
            return cilogonErrorMessage(this.$route.query.cilogon_error)
        },
        confirmingRemoval: {
            get() {
                return this.pendingRemoval !== null
            },
            set(value) {
                if (!value) this.pendingRemoval = null
            }
        }
    },
    watch: {
        'person.uuid': {
            immediate: true,
            handler(uuid) {
                if (uuid) this.load()
            }
        }
    },
    mounted() {
        if (this.$route.query.cilogon_linked) {
            this.$store.commit('pushSuccess', 'Sign-in method linked.')
            const { cilogon_linked, ...query } = this.$route.query
            this.$router.replace({ path: this.$route.path, query })
        }
    },
    methods: {
        formatDateTime,
        async load() {
            this.loading = true
            try {
                const { data } = await api.get(`/api/people/${this.person.uuid}/identities`)
                this.identities = data
            } finally {
                this.loading = false
            }
        },
        async remove() {
            const identity = this.pendingRemoval
            await api.delete(`/api/people/${this.person.uuid}/identities/${identity.id}`)
            this.pendingRemoval = null
            this.identities = this.identities.filter(i => i.id !== identity.id)
            this.$store.commit('pushSuccess', 'Sign-in method removed.')
        },
        label(identity) {
            return identity.idp_name || identity.idp || 'Unknown provider'
        }
    }
}
</script>

<template>
  <div>
    <h2>Sign-in Methods</h2>
    <static-alert v-if="linkError" variant="danger" class="mb-4">
      {{ linkError }}
    </static-alert>

    <p class="mb-2 text-sm text-gray-600">
      Email and password sign-in is always available. These accounts can also be used to sign in.
    </p>

    <div v-if="loading" class="text-gray-500">
      Loading…
    </div>
    <p v-else-if="identities.length === 0" class="text-gray-600">
      No other sign-in methods are linked.
    </p>
    <table v-else class="mb-4">
      <thead>
        <tr>
          <th>Provider</th>
          <th>Email</th>
          <th>Linked</th>
          <th>Last used</th>
          <th />
        </tr>
      </thead>
      <tbody>
        <tr v-for="identity in identities" :key="identity.id">
          <td>
            {{ label(identity) }}
            <div v-if="identity.orcid" class="text-xs text-gray-600">
              {{ identity.orcid }}
            </div>
          </td>
          <td>{{ identity.email }}</td>
          <td>{{ formatDateTime(identity.created_at) }}</td>
          <td>{{ identity.last_login_at ? formatDateTime(identity.last_login_at) : '—' }}</td>
          <td>
            <button class="btn btn-xs red" @click="pendingRemoval = identity">
              Remove
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="cilogonEnabled && userIsPerson(person)" class="mt-4">
      <h3>Link another sign-in method</h3>
      <CILogonProviderPicker link class="mt-2" />
    </div>

    <modal-dialog v-model="confirmingRemoval" title="Remove sign-in method?">
      <p v-if="pendingRemoval">
        {{ label(pendingRemoval) }}<span v-if="pendingRemoval.email"> ({{ pendingRemoval.email }})</span>
        will no longer be able to sign in to this GPM account.
      </p>
      <button-row
        submit-text="Remove" submit-variant="red" @submitted="remove"
        @canceled="pendingRemoval = null"
      />
    </modal-dialog>
  </div>
</template>

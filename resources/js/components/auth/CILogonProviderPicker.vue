<script>
import { debounce } from 'lodash-es'
import api from '@/http/api'
import { cilogonEnabled, cilogonRedirectUrl } from '@/domain/cilogon'

export default {
    name: 'CILogonProviderPicker',
    props: {
        inviteCode: {
            type: String,
            default: null
        },
        link: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            enabled: cilogonEnabled,
            common: [],
            showOther: false,
            query: '',
            results: [],
            searching: false,
        }
    },
    watch: {
        query(q) {
            this.search(q)
        }
    },
    async mounted() {
        if (!this.enabled) return
        try {
            const { data } = await api.get('/auth/cilogon/idps')
            this.common = data.common || []
        } catch {
            this.common = []
        }
    },
    created() {
        this.search = debounce(async (q) => {
            if (q.trim().length < 2) {
                this.results = []
                return
            }
            this.searching = true
            try {
                const { data } = await api.get('/auth/cilogon/idps', { params: { q } })
                if (q === this.query) this.results = data.results || []
            } finally {
                this.searching = false
            }
        }, 250)
    },
    methods: {
        urlFor(idp) {
            return cilogonRedirectUrl({ idp: idp?.id, inviteCode: this.inviteCode, link: this.link })
        },
        openOther() {
            this.showOther = true
            this.$nextTick(() => this.$refs.search?.focus())
        }
    }
}
</script>

<template>
  <div v-if="enabled">
    <div class="flex flex-wrap gap-2">
      <a
        v-for="idp in common"
        :key="idp.id"
        class="btn w-auto px-4"
        :href="urlFor(idp)"
      >
        {{ idp.name }}
      </a>
      <button
        v-if="!showOther"
        type="button"
        class="btn w-auto px-4"
        @click="openOther"
      >
        Other institution…
      </button>
    </div>

    <div v-if="showOther" class="mt-3">
      <input
        ref="search"
        v-model="query"
        type="search"
        class="w-full"
        placeholder="Search for your institution"
        aria-label="Search for your institution"
        @keyup.enter.stop
      >
      <ul v-if="results.length" class="mt-1 border rounded max-h-64 overflow-y-auto divide-y">
        <li v-for="idp in results" :key="idp.id">
          <a class="block px-3 py-2 hover:bg-blue-50" :href="urlFor(idp)">{{ idp.name }}</a>
        </li>
      </ul>
      <p v-else-if="query.trim().length >= 2 && !searching" class="text-sm text-gray-600 mt-1">
        No matching institutions.
        <a class="link" :href="urlFor(null)">Browse all providers on CILogon</a>.
      </p>
    </div>
  </div>
</template>

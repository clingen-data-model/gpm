<script>
/**
 * Candidate rows from GET /api/groups/{uuid}/members/candidates:
 *   kind 'person' – someone already in the GPM (has_account tells whether they can log in;
 *                   has_idp_identity means a ClinGen account with their address exists)
 *   kind 'idp'    – a ClinGen (identity provider) account with no GPM record yet, one row per address
 */
export default {
    name: 'MemberSuggestions',
    props: {
        suggestions: {
            required: true,
            type: Array
        }
    },
    emits: [
        'selected'
    ],
    methods: {
        rowKey(row) {
            return `${row.kind}:${row.idp_id || row.uuid}:${row.email}`;
        },
        usesIdpAccount(row) {
            return row.kind === 'idp' || (!row.has_account && row.has_idp_identity);
        },
        badge(row) {
            if (row.kind === 'idp') {
                return { text: 'ClinGen account, not yet in GPM', color: 'blue' };
            }
            if (!row.has_account && row.has_idp_identity) {
                return { text: 'Has ClinGen account', color: 'blue' };
            }
            if (!row.has_account) {
                return { text: 'No GPM account yet', color: 'gray' };
            }
            return null;
        },
        useSuggestion(item) {
            this.$emit('selected', item);
        }
    }
}
</script>
<template>
  <div>
    <ul>
      <li
        v-for="suggestion in suggestions"
        :key="rowKey(suggestion)"
        class="flex justify-between items-start my-2"
        :class="{'text-gray-500': suggestion.already_member}"
      >
        <slot>
          <div class="text-sm">
            <div>
              {{ suggestion.name }}
              <badge v-if="badge(suggestion)" :color="badge(suggestion).color" class="ml-1">
                {{ badge(suggestion).text }}
              </badge>
            </div>
            <div class="text-xs text-gray-600">
              {{ suggestion.email }}<span v-if="suggestion.institution"> &middot; {{ suggestion.institution }}</span>
            </div>
          </div>
          <div v-if="suggestion.already_member" class="text-xs whitespace-nowrap">
            Already a member
          </div>
          <button
            v-else
            class="btn btn-xs whitespace-nowrap"
            @click="useSuggestion(suggestion)"
          >
            {{ usesIdpAccount(suggestion) ? 'Add using ClinGen account' : 'Add as member' }}
          </button>
        </slot>
      </li>
    </ul>
  </div>
</template>

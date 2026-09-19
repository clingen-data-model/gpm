<script>
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
        useSuggestion(item) {
            this.$emit('selected', item);
        }
    }
}
</script>
<template>
  <div>
    <ul>
      <li v-for="suggestion in suggestions" :key="suggestion.uuid" class="flex justify-between my-2" :class="{'text-gray-500': suggestion.alreadyMember}">
        <div>
          <div>{{ suggestion.name }}</div>
          <div class="text-xs text-gray-500">{{ suggestion.email }}</div>
          <div v-if="suggestion.type === 'clerk_user' || suggestion.isClerkOnly" class="text-xs text-blue-700">
            Found in ClinGen account system, not yet in GPM
          </div>
        </div>
        <div v-if="suggestion.alreadyMember">Already a member</div>
        <button v-else class="btn btn-xs" @click="useSuggestion(suggestion)">
          {{ suggestion.type === 'clerk_user' || suggestion.isClerkOnly ? 'Add to GPM' : 'Add as member' }}
        </button>
      </li>
    </ul>
  </div>
</template>
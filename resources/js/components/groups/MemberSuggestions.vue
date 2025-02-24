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
            <li 
                v-for="suggestion in suggestions" 
                :key="suggestion.uuid" class="flex justify-between my-2"
                :class="{'text-gray-500': suggestion.alreadyMember}"
            >
                <slot>
                    {{suggestion.name}} 
                    <div v-if="suggestion.alreadyMember">Already a member</div>
                    <button 
                        v-else
                        class="btn btn-xs" 
                        @click="useSuggestion(suggestion)"
                    >
                        Add as member
                    </button>
                </slot>
            </li>
        </ul>
    </div>
</template>
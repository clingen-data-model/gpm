<template>
    <div>
        <ul>
            <li 
                v-for="suggestion in suggestions" 
                :key="suggestion.uuid" class="flex justify-between my-2"
            >
                <slot>
                    {{suggestion.name}} 
                    <button 
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
<script>
export default {
    name: 'ComponentName',
    props: {
        suggestions: {
            required: true,
            type: Array
        }
    },
    emits: [
        'selected',

    ],
    data() {
        return {
            
        }
    },
    computed: {

    },
    methods: {
        useSuggestion(item) {
            this.$emit('selected', item);
        }
    }
}
</script>
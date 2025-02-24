<script setup>
    import {computed} from 'vue';
    const props = defineProps({
        person: {
            type: Object,
            default: () => ({
                credentials: [],
                legacy_credentials: null
            })
        },
        legacyExpertise: {
            required: false,
        }
    });

    const hasExpertises = computed(() => {
        return props.person.expertises && props.person.expertises.length > 0;
    })
</script>

<template>
    <span>
        <span v-if="hasExpertises">
            {{ props.person.expertises.map(c => c.name).join(', ') }}
        </span>
        <span v-else-if="props.legacyExpertise">
            {{ props.legacyExpertise }}
            <note class="inline">(Legacy data)</note>
        </span>
        <span v-else>
            --
        </span>
    </span>
</template>


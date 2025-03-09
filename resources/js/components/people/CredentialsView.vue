<script setup>
    import {computed} from 'vue';
    const props = defineProps({
        person: {
            type: Object,
            default: () => ({
                credentials: [],
                legacy_credentials: null
            })
        }
    });

    const hasCredentials = computed(() => {
        if (props.person.credentials && props.person.credentials.length > 0) {
            return true;
        }
        return false;
    });

    const hasLegacyCredentials = computed(() => {
        return Boolean(props.person.legacy_credentials)
    })

    const displayCredentials = computed( () => {
        if (hasCredentials.value) {
            return props.person.credentials.map(c => c.name).join(', ');
        }
        return props.person.legacy_credentials || '--';
    })
</script>

<template>
    <span>
        {{ displayCredentials }}
        <note v-if="!hasCredentials && hasLegacyCredentials">(Legacy data)</note>
    </span>
</template>


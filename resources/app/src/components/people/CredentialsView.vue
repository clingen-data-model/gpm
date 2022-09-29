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

    const displayCredentials = computed( () => {
        if (props.person.credentials && props.person.credentials.length > 0) {
            return props.person.credentials.map(c => c.name).join(', ');
        }
        return props.person.legacy_credentials || '--';
    })
</script>

<template>
    <span>
        {{displayCredentials}}
    </span>
</template>


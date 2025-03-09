<script>
export default {
    name: 'RequirementsItem',
    props: {
        requirement: {
            type: Object,
            required: true
        }
    },
}
</script>
<template>
    <div class="flex space-x-2">
        <icon-checkmark v-if="requirement.isMet" class="text-green-600"></icon-checkmark>
        <icon-exclamation v-else class="text-yellow-400"></icon-exclamation>
        <div>{{ requirement.label }}</div>
    </div>
</template>
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
        <icon-checkmark class="text-green-600" v-if="requirement.isMet"></icon-checkmark>
        <icon-exclamation class="text-yellow-400" v-else></icon-exclamation>
        <div>{{requirement.label}}</div>
    </div>
</template>
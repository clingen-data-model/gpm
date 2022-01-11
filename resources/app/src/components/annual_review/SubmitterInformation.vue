<style lang="postcss" scoped>
    .csv-item:after {
        content: ', ';
    }
    .csv-item:last-child:after {
        content: '';
    }
</style>
<template>
    <div>
        <dictionary-row label="Expert Panel">{{group.displayName}}</dictionary-row>
        <input-row label="Submitting Member" label-class="font-bold">
            <select v-model="workingCopy.submitter_id">
                <option :value="null">Select...</option>
                <option v-for="member in members" :key="member.id" :value="member.id">{{member.person.name}}</option>
            </select>
        </input-row>
        <dictionary-row label="Cooridnator(s)">
            <span class="csv-item" 
                v-for="coordinator in group.coordinators" :key="coordinator.id"
            >{{coordinator.person.name}}</span>
        </dictionary-row>
    </div>
</template>
<script>
import setupWorkingMirror from '@/composables/setup_working_mirror'

export default {
    name: 'SubmitterInformation',
    props: {
        modelValue: {
            type: Object,
            required: true
        }
    },
    emits: [
        'update:modelValue',
    ],
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew']
        },
        members () {
            return this.group.members.filter(m => m !== null);
        }
    },
    setup(props, context) {
        const {workingCopy} = setupWorkingMirror(props, context);

        return {
            workingCopy
        }
    }
}
</script>
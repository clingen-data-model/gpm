<script>
import configs from '@/configs.json'
import { setupMirror } from '@/composables/setup_working_mirror'

export default {
    name: 'GroupMembersFilter',
    props: {
        modelValue: {
            required: true,
            type: Object
        }
    },
    // emits: mirrorEmits,
    emits: ['update:modelValue'],
    computed: {
        roles () {
            return configs.groups.roles;
        },
    },
    setup(props, context) {
        const workingCopy = setupMirror(props, context);

        return {
            workingCopy
        }
    }
}
</script>
<template>
    <div>
        <div class="flex justify-between px-2 space-x-2 bg-blue-200 rounded-lg">
            <div class="flex-1">
                <input-row label="Keyword" type="text" v-model="workingCopy.keyword" label-width-class="w-20"></input-row>
                <input-row label="Role" label-width-class="w-20">
                <select v-model="workingCopy.roleId">
                    <option :value="null">Select&hellip;</option>
                    <option 
                        v-for="role in roles"
                        :key="role.id"
                        :value="role.id"
                    >
                        {{role.name}}
                    </option>
                </select>
            </input-row>
            </div>
            <div class="flex-1 py-2">
                <checkbox class="block" label="Needs COI" v-model="workingCopy.needsCoi" />
                <!-- <checkbox class="block" label="Needs Training" v-model="workingCopy.needsTraining" /> -->
            </div>
            <div class="flex-1 py-2">
                <checkbox class="block" label="Hide Retired/Alumni" v-model="workingCopy.hideAlumns" />
            </div>
        </div>
    </div>
</template>
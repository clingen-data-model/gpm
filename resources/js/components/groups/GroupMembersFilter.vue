<script>
import { setupMirror } from '@/composables/setup_working_mirror'
import configs from '@/configs.json'

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
    setup(props, context) {
        const workingCopy = setupMirror(props, context);

        return {
            workingCopy
        }
    },
    computed: {
        roles () {
            return configs.groups.roles;
        },
    }
}
</script>
<template>
  <div>
    <div class="flex justify-between px-2 space-x-2 bg-blue-200 rounded-lg">
      <div class="flex-1">
        <input-row v-model="workingCopy.keyword" label="Keyword" type="text" label-width-class="w-20" />
        <input-row label="Role" label-width-class="w-20">
          <select v-model="workingCopy.roleId">
            <option :value="null">
              Select&hellip;
            </option>
            <option 
              v-for="role in roles"
              :key="role.id"
              :value="role.id"
            >
              {{ role.name }}
            </option>
          </select>
        </input-row>
      </div>
      <div class="flex-1 py-2">
        <checkbox v-model="workingCopy.needsCoi" class="block" label="Needs COI" />
        <!-- <checkbox class="block" label="Needs Training" v-model="workingCopy.needsTraining" /> -->
      </div>
      <div class="flex-1 py-2">
        <checkbox v-model="workingCopy.hideAlumns" class="block" label="Hide Retired/Alumni" />
      </div>
    </div>
  </div>
</template>
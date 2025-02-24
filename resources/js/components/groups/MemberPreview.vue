<script>
import ProfilePicture from '@/components/people/ProfilePicture.vue'
import {formatDate} from '@/date_utils'
import Group from '@/domain/group';
import GroupMember from '@/domain/group_member';
import CredentialsView from '../people/CredentialsView.vue';
import ExpertisesView from '../people/ExpertisesView.vue'



export default {
    name: 'MemberPreview',
    components: {
        ProfilePicture,
        CredentialsView,
        ExpertisesView
    },
    props: {
        member: {
            type: GroupMember,
            required: true
        },
        group: {
            type: Group,
            required: true
        }
    },
    emits: [
        'edit',
    ],
    setup () {
        return {
            formatDate
        }
    }
}
</script>
<template>
  <div class="px-8 py-4 inset">
    <static-alert v-if="member.isRetired" variant="warning" class="mb-3 float-right">
      RETIRED
    </static-alert>

    <div class="md:flex flex-wrap space-x-4 text-sm">
      <div>
        <ProfilePicture :person="member.person" style="width: 100px; height: 100px;" class="rounded" />
        <note>member id: {{ member.id }}</note>
      </div>
      <div class="flex-1 md:flex flex-wrap">
        <div class="flex-1 mr-8">
          <dictionary-row label="Email">
            {{ member.person.email }}
          </dictionary-row>
          <dictionary-row label="Institution">
            {{ member.person.institution ? member.person.institution.name : '--' }}
          </dictionary-row>
          <dictionary-row label="Credentials">
            <CredentialsView :person="member.person" />
          </dictionary-row>
          <dictionary-row label="Expertise">
            <ExpertisesView :person="member.person" :legacy-expertise="member.legacy_expertise" />
          </dictionary-row>
          <object-dictionary
            :obj="member"
            :only="['notes']"
          />
          <dictionary-row label="Start - End">
            {{ formatDate(member.start_date) }} - {{ formatDate(member.end_date) || 'present' }}
          </dictionary-row>
        </div>
        <div class="flex-1 mr-4">
          <div class="mt-2">
            <h4>Roles:</h4>
            <div class="ml-2">
              {{ member.roles.length > 0 ? member.roles.map(i => titleCase(i.name)).join(', ') : '--' }}
            </div>
          </div>
          <div v-if="member.hasRole('biocurator')">
            <h4>Biocurator Training:</h4>
            <div class="ml-2">
              <dictionary-row label="Level 1 training">
                <icon-checkmark v-if="member.training_level_1" class="text-green-700" />
              </dictionary-row>
              <dictionary-row label="Level 2 training">
                <icon-checkmark v-if="member.training_level_2" class="text-green-700" />
              </dictionary-row>
            </div>
          </div>

          <div class="mt-2">
            <h4>Extra Permissions:</h4>
            <div class="ml-2">
              {{ member.permissions.length > 0 ? member.permissions.map(i => i.name).join(', ') : '--' }}
            </div>
          </div>
        </div>
      </div>
      <div />
    </div>
    <router-link
      class="link"
      :to="{name: 'PersonDetail', params: {uuid: member.person.uuid}}"
    >
      View profile
    </router-link>
  </div>
</template>

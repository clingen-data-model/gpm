<script>
import GroupMember from '@/domain/group_member';
import Group from '@/domain/group';
import ProfilePicture from '@/components/people/ProfilePicture.vue'
import CredentialsView from '../people/CredentialsView.vue';
import ExpertisesView from '../people/ExpertisesView.vue'
import {formatDate} from '@/date_utils'
import ScopeOfWorkMemberRoles from './ScopeOfWorkMemberRoles.vue'
import { memberRetirementTransition } from '@/composables/scope_of_work_member_rows'



export default {
  name: 'MemberPreview',
  components: {
    ProfilePicture,
    CredentialsView,
    ExpertisesView,
    ScopeOfWorkMemberRoles
  },
  props: {
    comparison: { type: Object, default: null },
    snapshotOnly: Boolean,
    member: {
      type: [GroupMember, Object],
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
      formatDate,
      memberRetirementTransition
    }
  },
  computed: {
    isCoreApprovalMember() {
      return this.member.hasRole('core-approval-member')
    }
  }    
}
</script>
<template>
  <div v-if="snapshotOnly" class="px-8 py-4 inset text-sm">
    <dictionary-row label="Name">{{ member.label }}</dictionary-row>
    <dictionary-row v-if="Object.hasOwn(member, 'id')" label="Member ID">{{ member.id }}</dictionary-row>
    <dictionary-row v-if="Object.hasOwn(member, 'email')" label="Email">{{ member.email || '—' }}</dictionary-row>
    <dictionary-row v-if="Object.hasOwn(member, 'institution')" label="Institution">{{ member.institution || '—' }}</dictionary-row>
    <dictionary-row v-if="Object.hasOwn(member, 'credentials')" label="Credentials">{{ member.credentials?.join(', ') || '—' }}</dictionary-row>
    <dictionary-row v-if="Object.hasOwn(member, 'expertises')" label="Expertise">{{ member.expertises?.join(', ') || '—' }}</dictionary-row>
    <dictionary-row v-if="Object.hasOwn(member, 'start_date')" label="Start">{{ formatDate(member.start_date) || '—' }}</dictionary-row>
    <dictionary-row v-if="Object.hasOwn(member, 'end_date')" label="End">{{ member.end_date === null ? 'present' : formatDate(member.end_date) }}</dictionary-row>
    <dictionary-row v-if="memberRetirementTransition(comparison)" label="Retirement">{{ memberRetirementTransition(comparison) }}</dictionary-row>
    <dictionary-row v-if="Object.hasOwn(member, 'notes')" label="Notes">{{ member.notes || '—' }}</dictionary-row>
    <dictionary-row label="Roles">
      <ScopeOfWorkMemberRoles v-if="member.roles" :roles="member.roles" :comparison="comparison" snapshot-only preview />
      <span v-else>Not captured</span>
    </dictionary-row>
  </div>
  <div v-else class="px-8 py-4 inset">
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
          <dictionary-row v-if="memberRetirementTransition(comparison)" label="Retirement">{{ memberRetirementTransition(comparison) }}</dictionary-row>
        </div>
        <div class="flex-1 mr-4">
          <div class="mt-2">
            <h4>Roles:</h4>
            <div class="ml-2">
              <ScopeOfWorkMemberRoles :roles="member.roles" :comparison="comparison" preview />
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
          <div v-if="isCoreApprovalMember" class="mt-2">
            <h4>Core Approval Member Attestation:</h4>
            <div class="ml-2">
              {{ member.person.core_member_attestation_completed ? `Completed on ${formatDate(member.person.core_member_attestation_completion_date)}` : 'Attestation Required'   }}
            </div>
          </div>
        </div>
      </div>
      <div />
    </div>
    <router-link
      v-if="comparison?.operation !== 'removed'"
      class="link"
      :to="{name: 'PersonDetail', params: {uuid: member.person.uuid}}"
    >
      View profile
    </router-link>
  </div>
</template>

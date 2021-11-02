<template>
    <div class="px-8 py-4 inset">
        <static-alert variant="warning" v-if="member.isRetired" class="mb-3 float-right">
            RETIRED
        </static-alert>

        <div class="md:flex flex-wrap space-x-4 text-sm">
            <div>
                <profile-picture :person="member.person"></profile-picture>
                <note>member id: {{member.id}}</note>
            </div>
            <div class="flex-1 md:flex flex-wrap">
                <div class="flex-1 mr-8">
                    <dict-row label="Email">{{member.person.email}}</dict-row>
                    <dict-row label="Institution">{{member.person.institution_id ? member.person.institution.name : '--'}}</dict-row>
                    <dict-row label="Credentials">{{member.person.credentials}}</dict-row>
                    <object-dictionary
                        :obj="member"
                        :only="['expertise', 'notes']"
                    ></object-dictionary>
                        <dictionary-row label="Start - End">
                            {{formatDate(member.start_date)}} - {{formatDate(member.end_date) || 'present'}}
                        </dictionary-row>
                </div>
                <div class="flex-1 mr-4">
                    <h4>Biocurator Training:</h4>
                    <div v-if="member.hasRole('biocurator')"  class="ml-2">
                        <dictionary-row label="Level 1 training">
                            <checkmark class="text-green-700" v-if="member.training_level_1"/>
                        </dictionary-row>
                        <dictionary-row label="Level 2 training">
                            <checkmark class="text-green-700" v-if="member.training_level_2"/>
                        </dictionary-row>
                    </div>
                    <div class="mt-2">
                        <h4>Roles:</h4>
                        <div class="ml-2">
                            {{member.roles.length > 0 ? member.roles.map(i => titleCase(i.name)).join(', ') : '--'}}
                        </div>
                    </div>
                    <div class="mt-2">
                        <h4>Extra Permissions:</h4>
                        <div class="ml-2">
                            {{member.permissions.length > 0 ? member.permissions.map(i => i.name).join(', ') : '--'}}
                        </div>
                    </div>
                </div>
            </div>
            <div>
            </div>
        </div>
        <router-link class="link" 
            :to="{name: 'PersonDetail', params: {uuid: this.member.person.uuid}}">
            View profile
        </router-link>
    </div>
</template>
<script>
import GroupMember from '@/domain/group_member';
import Group from '@/domain/group';
import ProfilePicture from '@/components/people/ProfilePicture'
import {formatDate} from '@/date_utils'
import Checkmark from '@/components/icons/IconCheckmark'
import ExclamationIcon from '@/components/icons/IconExclamation'

export default {
    name: 'MemberPreview',
    components: {
        ProfilePicture,
        Checkmark,
        ExclamationIcon
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
            formatDate: formatDate
        }
    }
}
</script>
<template>
    <div class="px-8 py-4 inset">
        <static-alert variant="warning" v-if="member.isRetired" class="mb-3 float-right">
            RETIRED
        </static-alert>

        <div class="flex flex-wrap space-x-4 text-sm">
            <div>
                <profile-picture :person="member.person"></profile-picture>
                <note>member id: {{member.id}}</note>
            </div>
            <div class="flex-1">
                <object-dictionary 
                    :obj="member.person" 
                    :only="['email', 'inistitution', 'credentials']"
                ></object-dictionary>

                <dictionary-row label="Start - End">
                    {{formatDate(member.start_date)}} - {{formatDate(member.end_date) || 'present'}}
                </dictionary-row>
            </div>
            <div class="flex-1">
                <dictionary-row label="Roles">
                    {{member.roles.length > 0 ? member.roles.map(i => i.name).join(', ') : '--'}}
                </dictionary-row>
                <dictionary-row label="Extra Permissions">
                    {{member.permissions.length > 0 ? member.permissions.map(i => i.name).join(', ') : '--'}}
                </dictionary-row>
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
import EditButton from '@/components/buttons/EditIconButton'
import ProfilePicture from '@/components/people/ProfilePicture'
import {formatDate} from '@/date_utils'

export default {
    name: 'MemberPreview',
    components: {
        EditButton,
        ProfilePicture,
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
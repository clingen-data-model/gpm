<template>
    <div>
        <dev-todo :items="[
            'Refactor to handle Edit',
            'Populate perms based on checked roles',
            'Wire-up save/update actions',
            'Wire-up role and permission actions'
        ]"></dev-todo>
        
        <input-row label="Name">
            <div class="flex space-x-2">
                <input type="text" v-model="newMember.first_name" placeholder="First" class="block w-1/2">
                <input type="text" v-model="newMember.last_name" placeholder="Last" class="block w-1/2">
            </div>
        </input-row>
        <input-row label="Email" v-model="newMember.email" placeholder="example@example.com" input-class="w-full"></input-row>
        
        <div class="border-t mt-4 pt-2">
            <h3>Group Roles</h3>
            <div class="flex flex-col h-24 flex-wrap">
                <label v-for="role in roles" :key="role.id">
                    <input type="checkbox" v-model="newMember.roles" :value="role">
                    {{role.name}}
                </label>
            </div>
        </div>

        <div class="border-t mt-4 pt-2">
            <h3>Group Permissions</h3>
            <div class="flex flex-col h-24 flex-wrap">
                <checkbox v-for="permission in permissions" v-model="newMember.permissions" :value="permission" :key="permission.id">{{permission.name}}</checkbox>
            </div>
        </div>

        <button-row
            @submit="save"
            @cancel="cancel"
        >
        </button-row>
    </div>
</template>
<script>
import {groups} from '@/configs'

export default {
    name: 'AddMemberForm',
    props: {
        
    },
    emits: [
        'saved',
        'canceled',
        'closed'
    ],
    data() {
        return {
            newMember: {},
            roles: groups.roles,
            permissions: groups.permissions
        }
    },
    computed: {

    },
    methods: {
        initNewMember() {
            this.newMember = {
                first_name: null,
                last_name: null,
                email: null,
                roles: [],
                permissions: []
            }
        },
        clearForm () {
            this.initNewMember();
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        save () {
            // do the save
            this.clearForm();
            this.$emit('saved');
        }
    },
    mounted () {
        this.initNewMember();
    }
}
</script>
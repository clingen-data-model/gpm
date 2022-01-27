<template>
    <div>
        <h1>{{user.person.name || 'loading...'}}</h1>

        <object-dictionary :obj="userInfo"></object-dictionary>
        <button class="btn btn-xs" @click="initEdit" v-if="canEditUser">Edit system roles &amp; permissions</button>

        <h2 class="mt-8 mb-2">Memberships</h2>
        <data-table :fields="membershipFields" :data="membershipInfo"></data-table>

        <teleport to="body">
            <modal-dialog 
                v-model="showEditForm" 
                title="Edit System Roles & Permissions"
            >
                <h3 class="border-b mb-1">Roles</h3>
                <ul class="flex flex-wrap">
                    <li v-for="role in availableSystemRoles" :key="role.id" class="w-1/3 h-12">
                        <checkbox v-model="checkedRoles" :label="titleCase(role.name)" :value="role.id" />
                    </li>
                </ul>

                <h3 class="border-b mb-1">Permissions</h3>
                <ul class="flex flex-wrap">
                    <li v-for="permission in availableSystemPermissions" :key="permission.id" class="w-1/3 h-12">
                        <checkbox 
                            v-if="checkedRolePermissionIds.includes(permission.id)"
                            :modelValue="true" :disabled="true" :label="permission.name"
                        />
                        <checkbox 
                            v-else
                            v-model="checkedPermissions"
                            :label="titleCase(permission.name)" 
                            :value="permission.id" 
                        />
                    </li>
                </ul>

                <div class="px-2 py-1 mb-2 mt-4 bg-gray-100 border relative text-xs">
                    <div class="flex space-x-2">
                    <strong>Legend: </strong>
                        <checkbox label="Not granted" />
                        <checkbox :value="1" :modelValue="true" label="Granted" />
                        <checkbox :value="2" :modelValue="true"  disabled label="Granted w/ role" />
                    </div>
                    <div class="absolute top-0 left-0 w-full h-full bg-pink-500 opacity-0">&nbsp;</div>
                </div>

                <button-row 
                    submit-text="Save" 
                    @submitted="saveRolesAndPermissions"
                    @canceled="closeEditForm"
                ></button-row>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import User from '@/domain/user'
import {api} from '@/http'
import configs from '@/configs'

export default {
    name: 'UserDetail',
    props: {
        id: {
            require: true
        }
    },
    data() {
        return {
            user: new User(),
            membershipFields: [
                {name: 'id'},
                {name: 'group'},
                {name: 'roles'},
                {name: 'extra_permissions'}
            ],
            showEditForm: false,
            systemRoles: [],
            systemPermissions: Object.values(configs.system.permissions),
            configs,
            checkedRoles: [],
            checkedPermissions: [],
        }
    },
    computed: {
        userInfo () {
            return {
                name: this.user.person.name,
                email: this.user.person.email,
                roles: this.user.roles.map(r => r.name).join(', '),
                additional_permissions: this.user.permissions.map(r => r.name).join(', '),
                registered: this.formatDate(this.user.created_at),
            }
        },
        membershipInfo () {
            return this.user.person.memberships.map(m => ({
                    id: m.id,
                    group: m.group.expert_panel.display_name,
                    roles: m.roles.map(r => r.name).join(', '),
                    extra_permissions: m.permissions.map(r => r.name).join(', ') || '',
                    is_contact: m.is_contact ? 'Yes' : 'No',
                }))
        },
        checkedRolePermissionIds () {
            if (!this.systemRoles) {
                return [];
            }
            const checkedRolePerms = this.systemRoles
                                        .filter(r => this.checkedRoles.includes(r.id))
                                        .map(r => r.permissions)
                                        .flat()
                                        .map(p => p.id);
            return [...(new Set(checkedRolePerms))];
        },
        availableSystemRoles () {
            return this.systemRoles.filter(r => {
                if (this.hasRole('super-user')) return true;
                if (this.hasRole('super-admin')) {
                    if (r.name === 'super-user') return false;
                    return true;
                }
                return false;
            });
        },
        availableSystemPermissions () {
            return this.systemPermissions.filter(p => {
                if (this.hasRole('super-user')) return true;
                if (this.hasRole('super-admin')) {
                    if (p.name === 'logs-view') return false;
                    return true;
                }
                return false;
            });
        },
        canEditUser () {
            const currentUser = this.$store.getters.currentUser;
            if (!this.hasPermission('users-manage')) {
                return false;
            }

            if (this.currentUserIsUser) {
                return true;
            }

            if (currentUser.hasRole('super-user')) {
                return true;
            }

            if (currentUser.hasRole('super-admin') && this.user.hasRole('admin')) {
                return true;
            }

            return false;
            
        },
        currentUserIsUser () {
            return this.$store.getters.currentUser.id == this.user.id;
        }
    },
    watch: {
        id: {
            immediate: true,
            handler () {
                this.getUser();
            }
        }
    },
    methods: {
        async getUser () {
            this.user = await api.get(`/api/users/${this.id}`)
                            .then(response => {
                                return new User(response.data)
                            });
            this.resetCheckedRolesAndPermissions();
        },
        resetCheckedRolesAndPermissions () {
            this.checkedRoles = this.user.roles.map(r => r.id);
            this.checkedPermissions = this.user.permissions.map(r => r.id);            
        },
        async getSystemRoles () {
            this.systemRoles = await api.get(`/api/roles`)
                                .then(response => response.data);
        },
        initEdit () {
            this.showEditForm = true;
        },
        async saveRolesAndPermissions () {
            const payload = {
                role_ids: this.checkedRoles, 
                permission_ids: this.checkedPermissions
            };

            await api.put(`/api/users/${this.user.id}/roles-and-permissions`, payload);
            this.showEditForm = false;

            this.getUser();

        },
        closeEditForm () {
            this.resetCheckedRolesAndPermissions();
            this.showEditForm = false;
        }
    },
    mounted () {
        this.getSystemRoles();
    }
}
</script>
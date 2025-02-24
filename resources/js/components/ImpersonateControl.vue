<template>
    <div>
        <div v-if="canImpersonate || user.is_impersonating">
            <button v-if="!user.is_impersonating"
                @click="showSelector = !showSelector"
                class="btn btn-xs border" 
            >
                Impersonate a user
            </button>
            <a v-else
                href="/impersonate/leave"
                class="btn btn-secondary btn-xs"
                @click="clearingImpersonate = true"
            >
                Stop impersonating
            </a>
            <teleport to='body'>
                <modal-dialog
                    title="Impersonate another user" 
                    v-model="showSelector"
                    @ok="impersonateSelected"
                >
                    <SearchSelect 
                        v-model="selectedUser"
                        :search-function="search"
                        placeholder="search by name, email, or numeric id"
                    >
                        <template v-slot:selection-label="{selection}">
                            {{selection.name}} ({{selection.email}})
                        </template>
                        <template v-slot:option="{option}">
                            {{option.name}} ({{option.email}})
                        </template>
                    </SearchSelect>

                    <button-row 
                        @submit-text="search"
                        @submitted="impersonateSelected" 
                        @cancel="cancelImpersonate"
                    />
                    <hr>
                    <note>
                        Note that only ClinGen members that have redeemed their invite will be users of the system.
                        <br>
                        If they have not redeemed their invite you cannot impersonate them.
                        <br>
                        Check the <a href="/admin/invites" target="invites-admin">Invites Admin</a>.
                    </note>
                </modal-dialog>
                <modal-dialog
                    v-model="showProgress" 
                >
                    <h3 class="text-center" v-if="selectedUser">
                        {{progressMessage}}
                    </h3>
                    <p class="text-center"> 
                        The page will reload in a moment...
                    </p>
                </modal-dialog>
            </teleport>
        </div>
    </div>
</template>
<script>
import {mapGetters} from 'vuex';
import {impersonate, search} from '../domain/impersonate_service';
import SearchSelect from '@/components/forms/SearchSelect.vue';

export default {
    components: {
        SearchSelect
    },
    data() {
        return {
            selectedUser: null,
            showSelector: false,
            showProgress: false,
            clearingImpersonate: false
        }
    },
    computed: {
        ...mapGetters({
            user: 'currentUser'
        }),
        canImpersonate () {
            return this.$store.getters.currentUser.hasRole('super-user')
                || this.$store.getters.currentUser.hasRole('super-admin')
                || this.$store.getters.currentUser.hasRole('admin');
        },
        progressMessage () {
            if (this.clearingImpersonate) {
                return 'Clearing impersonation.';
            }
            return `Impersonating ${this.selectedUser.name}`;
        }
    },
    methods: {
        impersonateSelected() {
            if (this.selectedUser) {
                this.$emit('impersonated');
                this.showProgress = true;
                impersonate(this.selectedUser.id)
            }
            
        },
        async search (keyword) {
            return await search(keyword);
        },
        cancelImpersonate () {
            this.selectedUser = null;
            this.showSelector = !this.showSelector;
        }
    }
}
</script>
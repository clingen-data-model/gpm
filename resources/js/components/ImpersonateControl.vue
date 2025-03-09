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
<template>
    <div>
        <div v-if="canImpersonate || user.is_impersonating">
            <button v-if="!user.is_impersonating"
                class="btn btn-xs border"
                @click="showSelector = !showSelector" 
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
                    v-model="showSelector" 
                    title="Impersonate another user"
                    @ok="impersonateSelected"
                >
                    <SearchSelect 
                        v-model="selectedUser"
                        :search-function="search"
                        placeholder="search by name, email, or numeric id"
                    >
                        <template #selection-label="{selection}">
                            {{ selection.name }} ({{ selection.email }})
                        </template>
                        <template #option="{option}">
                            {{ option.name }} ({{ option.email }})
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
                    <h3 v-if="selectedUser" class="text-center">
                        {{ progressMessage }}
                    </h3>
                    <p class="text-center"> 
                        The page will reload in a moment...
                    </p>
                </modal-dialog>
            </teleport>
        </div>
    </div>
</template>
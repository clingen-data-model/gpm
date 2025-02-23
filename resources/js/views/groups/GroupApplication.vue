<script>
import ApplicationGcep from '@/components/expert_panels/ApplicationGcep.vue';
import ApplicationVcep from '@/components/expert_panels/ApplicationVcep.vue';
import ApplicationMenu from '@/components/layout/ApplicationMenu.vue';
import { getApplicationForGroup } from "@/composables/use_application.js";
import Group from '@/domain/group';

export default {
    name: 'GroupApplication',
    components: {
        ApplicationGcep,
        ApplicationVcep,
        ApplicationMenu,
    },
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            showDocumentation: false,
            showModal: false,
            showApplicationToc: true,
            lastSavedAt: new Date(),
            saving: false
        }
    },
    watch: {
        $route: function () {
            this.showModal = this.$route.meta.showModal
                                ? Boolean(this.$route.meta.showModal)
                                : false;
        },

        uuid: {
            immediate: true,
            handler: async function (to) {
                await this.$store.dispatch('groups/find', to)
                    .then(() => {
                        this.$store.commit('groups/setCurrentItemIndexByUuid', this.uuid)
                    });
                await this.$store.dispatch('groups/getSubmissions', this.group);

            }
        },
    },
    computed: {
        applicationComponent() {
            if (this.group?.is_vcep_or_scvcep) {
                return ApplicationVcep;
            }

            if (this.group?.is_gcep) {
                return ApplicationGcep;
            }

            return null;
        },
        group () {
            const group = this.$store.getters['groups/currentItem'] || new Group();
            return group || new Group();
        },
        application() {
            return getApplicationForGroup(this.group);
        },
    },
    methods: {
        hideModal () {
            this.$router.replace({name: 'GroupApplication', params: {uuid: this.uuid}});
        },
        handleModalClosed (evt) {
            this.clearModalForm(evt);
            this.$router.push({name: 'GroupApplication', params: {uuid: this.uuid}});
        },
        clearModalForm () {
            if (typeof this.$refs.modalView.clearForm === 'function') {
                this.$refs.modalView.clearForm();
            }
        },
        updateLastSavedAt () {
            this.saving = false;
            this.lastSavedAt = new Date();
        },
    },
    beforeUnmount() {
        this.$store.commit('groups/clearCurrentItem')
    },
    beforeRouteLeave() {
        if (this.$refs.application.applicationIsDirty()) {
            // eslint-disable-next-line no-alert
            const confirm = window.confirm('You have unsaved changes. If you continue your changes may be lost.');

            if (!confirm) {
                return false;
            }
        }
    }
}
</script>
<template>
    <div>
        <header class="flex items-center pb-2 border-b z-20">
            <div class="flex-1">
                <group-breadcrumbs :group="group" />
                <h1 class="border-b-0 flex justify-between items-start mb-0">
                    <div>
                        {{group.displayName}} - Application
                        <span v-if="hasPermission('groups-manage')">
                            <note class="inline">group: {{group.id}}</note>
                            <span class="note">&nbsp;|&nbsp;</span>
                            <note class="inline">expert_panel: {{group.expert_panel.id}}</note>
                        </span>
                    </div>

                    <button
                        v-if="!group.expert_panel.hasPendingSubmission"
                        @click="$refs.application.save"
                        class="btn btn-sm"
                    >Save</button>
                </h1>
            </div>
        </header>
        <div class="md:flex">
            <application-menu
                class="mt-4"
                :application="application"
                :is-collapsed="!showApplicationToc"
            >
                <template v-slot:footer>
                    <div class="my-4 p-4 text-sm flex items-start space-x-4 bg-gray-200 rounded-lg">
                        <icon-question height="48" width="48" class="text-blue-700"/>
                        <div>
                            <h3 style="line-height: 1">Have Questions?</h3>
                            <div style="font-size: 1rem; line-height:2">
                                Read the
                                <gcep-quick-guide-link v-if="group.group_type_id == 3" />
                                <vcep-protocol-link v-if="group.group_type_id == 4" />
                            </div>
                        </div>
                    </div>
                    <div class="text-sm bottom-0">
                        <div v-if="saving">Saving...</div>
                        <div v-else>Last saved at {{formatTime(lastSavedAt)}}</div>
                    </div>
                </template>
            </application-menu>
            <div class=" flex-1">
                <section id="body" class="px-4" v-remaining-height>
                    <static-alert
                        v-if="group.expert_panel.hasPendingSubmission"
                        class="relative mt-4 px-4 z-50"
                        variant="success"
                    >
                        <p class="text-lg">Your application was submitted on {{formatDate(group.expert_panel.pendingSubmission.created_at)}}.</p>
                        <p>You cannot update your application while waiting approval.</p>
                        <p>The approval committee will respond soon.</p>
                        <p>
                            Please contact
                            <a href="mailto:cdwg_oversightcommittee@clinicalgenome.org">
                                the ClinGen CDWG Oversight Committee
                            </a>
                            if you have any questions.
                        </p>
                    </static-alert>
                    <component
                        :is="applicationComponent"
                        ref="application"
                        @saved="updateLastSavedAt"
                        @saving="saving = true"
                    ></component>
                </section>
            </div>
        </div>
        <teleport to="body">
            <modal-dialog v-model="showModal" @closed="handleModalClosed" :title="this.$route.meta.title">
                <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
            </modal-dialog>
        </teleport>
    </div>
</template>
<style scoped>
    .application-content {
        @apply md:w-3/4 flex-auto;
    }
    .application-nav {
        @apply flex-initial pt-4 mr-4;
        transition: all .75s ease;
    }
    .application-nav.expanded {
        width: 25%;
    }
    .application-nav.collapsed {
        width: 0px;
        margin-right: 0px;
        border: none;
    }
</style>

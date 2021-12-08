<template>
    <div>
        <header class="flex items-center pb-2 border-b z-20">
            <button 
                class="-mb-4 bg-transparent mr-4 outline-none focus:outline-none" 
                @click="showApplicationToc = !showApplicationToc"
            >
                <icon-menu></icon-menu>
            </button>
            <div class="flex-1">
                <router-link class="note"
                    :to="{name: 'GroupDetail', params: {uuid: group.uuid}}"
                    v-if="group.uuid"
                >
                    {{group.displayName}}
                </router-link>
                <h1 class="border-b-0 flex justify-between items-start mb-0">
                    <div>
                        {{group.displayName}} - Application
                        <span v-if="hasPermission('groups-manage')">
                            <note class="inline">group: {{group.id}}</note>
                            <span class="note">&nbsp;|&nbsp;</span>
                            <note class="inline">expert_panel: {{group.expert_panel.id}}</note>
                        </span>
                    </div>
                    
                    <button class="btn btn-sm" @click="$refs.application.save">Save</button>
                </h1>
            </div>
        </header>
        <div class="md:flex">
            <application-menu :menu="applicationMenu" :is-collapsed="!showApplicationToc"></application-menu>
            <div class=" flex-1">
                <section id="body" class="px-4" v-remaining-height>
                    <component :is="applicationComponent" ref="application"></component>
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
<script>
// included with relative link path b/c alias won't resolve for ApplicationGcep 🤷‍♀️
import ApplicationGcep from '../../components/expert_panels/ApplicationGcep.vue';
import ApplicationVcep from '@/components/expert_panels/ApplicationVcep';
import ApplicationMenu from '@/components/layout/ApplicationMenu';
import Group from '@/domain/group';
import MenuItem from '@/MenuItem.js';

const goToAnchor = (anchor) => {
    location.href = "#";
    location.href = `#${anchor}`
}

const goToSection = (section) => {
    return () => {
        goToAnchor(section)
    }
}

const goToPage = (page) => {
    return () => {
        goToAnchor(page)
    }
}

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
            showApplicationToc: true
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
            handler: function (to) {
                this.$store.dispatch('groups/find', to)
                    .then(() => {
                        this.$store.commit('groups/setCurrentItemIndexByUuid', this.uuid)
                    })

            }
        },
    },
    computed: {
        applicationComponent () {
            if (this.group && this.group.isVcep()) {
                return ApplicationVcep;
            }

            if (this.group && this.group.isGcep()) {
                return ApplicationGcep;
            }

            return null;
        },
        group () {
            const group = this.$store.getters['groups/currentItem'] || new Group();
            return group || new Group();
        },
        applicationMenu () {
            return [
                new MenuItem({
                    label: 'Group Definition',
                    contents: [
                        new MenuItem({label: 'Basic Information', handler: goToSection('basicInfo')}),
                        new MenuItem({label: 'Scope Definition', handler: goToSection('scope')}),
                        new MenuItem({label: 'Reanalysis & Discrepancy Resolution', handler: goToSection('reanalysis')}),
                        new MenuItem({label: 'NHGRI Data Availability', handler: goToSection('nhgri')}),
                    ],
                    handler: goToPage('definition'),
                }),
                new MenuItem({
                    label: 'Draft Specifications',
                    handler: goToPage('draft-specifications'),
                }),
                new MenuItem({
                    label: 'Pilot Specifications',
                    handler: goToPage('pilot-specifications'),
                }),
                new MenuItem({
                    label: 'Sustained Curation',
                    handler: goToPage('sustained-curation'),
                    contents: [
                        new MenuItem({
                            label: 'Curation and Review Process',
                            handler: goToSection('curationReviewProcess'),
                        }),
                        new MenuItem({
                            label: 'Evidence Summaries',
                            handler: goToSection('evidenceSummaries'),
                        }),
                        new MenuItem({
                            label: 'Member Designation',
                            handler: goToSection('designations'),
                        }),
                    ]
                })
            ]
        }
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
    },
    beforeUnmount() {
        this.$store.commit('groups/clearCurrentItem')
    },
}
</script>
<style lang="postcss" scoped>
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

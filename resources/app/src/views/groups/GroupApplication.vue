<template>
    <div>
        <header>
            <router-link class="note"
                :to="{name: 'GroupDetail', params: {uuid: group.uuid}}"
                v-if="group.uuid"
            >
                {{group.displayName}}
            </router-link>
            <h1 class="border-b flex justify-between items-start">
                {{group.displayName}} - Application
                <button class="btn btn-sm" @click="$refs.application.save">Save</button>
            </h1>
        </header>
        <div class="flex">
            <section id="appliation-sidebar" class="lg:w-1/4 xl:w-1/5 border-r -mt-4 pt-4 pr-4" v-remaining-height>
                Sidebar
            </section>
            <div class="bg-white flex-1 -mt-4">
                <!-- <div class="border-b px-4 py-2">
                    
                </div> -->
                <section id="body" class="py-4 px-4" v-remaining-height>
                    <component :is="applicationComponent" :group="group" ref="application"></component>
                </section>
            </div>
            
        </div>
    </div>
</template>
<script>
import ApplicationGcep from '@/components/expert_panels/ApplicationGcep';
import ApplicationVcep from '@/components/expert_panels/ApplicationVcep';

export default {
    name: 'GroupApplication',
    components: [
        ApplicationGcep,
        ApplicationVcep,
    ],
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
    data() {
        return {
        }
    },
    watch: {
        uuid: {
            immediate: true,
            handler: function (to) {
                this.$store.dispatch('groups/find', to)
                    .then(() => {
                        this.$store.commit('groups/setCurrentItemIndexByUuid', this.uuid)
                    })

            }
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItem'] ?? {};
        },
        applicationComponent () {
            if (this.group && this.group.expert_panel) {
                return this.group.isVcep() ? ApplicationVcep : ApplicationGcep;
            }

            return null;
        }
    },
    methods: {

    },
}
</script>
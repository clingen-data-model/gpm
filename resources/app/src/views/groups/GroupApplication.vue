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
                <section id="body" class="py-4 px-4" v-remaining-height>
                    <!-- <component :is="applicationComponent" v-model:group="group" ref="application"></component> -->
                    <application-gcep ref="application"></application-gcep>
                </section>
            </div>
            
        </div>
    </div>
</template>
<script>
import {ref, watch} from 'vue'
import {useStore} from 'vuex'
// import ApplicationGcep from '@/components/expert_panels/ApplicationGcep';
import ApplicationVcep from '@/components/expert_panels/ApplicationVcep';
import Group from '@/domain/group';
import ApplicationGcep from '../../components/expert_panels/ApplicationGcep.vue';

export default {
    name: 'GroupApplication',
    components: {
        ApplicationGcep,
        ApplicationVcep,
    },
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
        },
    },
    computed: {
        applicationComponent () {
            if (this.group && this.group.expert_panel) {
                return this.group.isVcep() ? ApplicationVcep : ApplicationGcep;
            }

            return null;
        },
        group () {
            const group = this.$store.getters['groups/currentItem'];
            console.log({group})
            return group || new Group();
        }
    },
    methods: {

    },
    beforeUnmount() {
        this.$store.commit('groups/clearCurrentItem')
    },
}
</script>
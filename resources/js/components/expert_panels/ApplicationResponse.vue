<script setup>
import {computed, provide, ref, watch, defineProps} from 'vue'
import {useStore} from 'vuex'
import DefinitionReview from '@/components/expert_panels/DefinitionReview.vue'
import SustainedCurationReview from '@/components/expert_panels/SustainedCurationReview.vue'
import SpecificationsSection from '@/components/expert_panels/SpecificationsSection'
import commentManagerFactory from '../../composables/comment_manager'

const props = defineProps({
    uuid: {
        type: String,
        required: true
    }
});
const store = useStore();

// eslint-disable-next-line
const commentManager = ref(commentManagerFactory('App\\Modules\\Group\\Models\\Group', 0));
provide('commentManager', null)

const group = computed(() => store.getters['groups/currentItemOrNew'])

const loadGroup = async (uuid) => {
    await store.dispatch('groups/findAndSetCurrent', uuid);
    store.dispatch('groups/getMembers', group.value);
    store.dispatch('groups/getMembers', group.value);
    store.dispatch('groups/getGenes', group.value);

}

watch(() => props.uuid, (to) => {
    loadGroup(to);
}, {immediate: true});


const print = () => {
    window.print();
}
</script>
<template>
    <div class="application-review">
        <div class="print:hidden">
            <router-link :to="{name: 'GroupList'}" class="note">Groups</router-link>
            <span class="note"> &gt; </span>
            <router-link v-if="group.uuid" :to="{name: 'GroupDetail', params: {uuid: group.uuid}}" class="note">
                {{group.displayName}}
            </router-link>
        </div>
        <div class="application-step">
            <h1 class="flex items-center justify-between">
                {{group.displayName}} - Group Definition
                <button class="btn btn-sm print:hidden" @click="print">
                    <strong>Print</strong>
                    &nbsp;
                    <icon-printer class="inline-block"></icon-printer>
                </button>
            </h1>
            <definition-review></definition-review>
        </div>
        <div class="step-break">
            End of Group Definition Application.
        </div>

        <div class="application-step print:hidden">
            <h1 v-if="group.expert_panel.definition_is_approved" class="print:hidden" >
                {{group.displayName}} - Specifications and Pilot
            </h1>
            <section v-if="group.expert_panel.definition_is_approved" class="print:hidden" >
                <specifications-section :doc-type-id="[3,4,7]" :readonly="true" />
            </section>
        </div>

        <div class="step-break">
            End of Specification Draft and Pilot.
        </div>

        <div class="application-step  page-break">
            <h1 v-if="group.expert_panel.has_approved_pilot">
                {{group.displayName}} - Sustained Curation
            </h1>

            <sustained-curation-review />
        </div>
    </div>
</template>
<style>
    .application-review {
        max-width: 800px;
    }

</style>

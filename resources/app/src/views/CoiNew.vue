<script setup>
    import {computed, ref, watch} from 'vue'
    import { useStore } from 'vuex';
    import coiDef from '../../../surveys/coi_v2.json'
    import CoiPolicy from '@/components/coi/CoiPolicy.vue'

    const props = defineProps({
        code: {
            type: String,
            required: true
        }
    })

    const store = useStore();

    const yesNoOptions = [
        {label: 'Yes', value: 1},
        {label: 'No', value: 0}
    ]

    const responseData = ref({});
    const errors = ref({});
    const verifying = ref(false);
    const saved = ref(false);
    const saving = ref(false);
    const redirectCountdown = ref(5);
    const showQuestions = ref(false);

    const codeIsValid = computed(() => {
        return this.groupName !== null;
    });
    const coiTitle = computed(() => {
        return `Conflict of Interest for ${membership.value.group.display_name}`;
    });
    const membership =  computed(() => {
        return store.getters
                .currentUser
                .person
                .memberships
                .find(m => {
                    return m.group
                        && m.group.coi_code === props.code
                });
    });

    const initResponseValues = () => {
        if (membership.value && membership.value.cois.length > 0) {
            responseData.value = {...membership.value.cois[this.membership.cois.length -1].data};
        }

        return {}
    };

    watch(() => props.code, () => initResponseValues());

</script>

<template>
    <card :title="coiTitle" class="mx-auto relative" style="max-width:800px">
        <CoiPolicy @next="showQuestions = true" />

    </card>
</template>

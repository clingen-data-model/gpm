<script setup>
    import {useStore} from 'vuex'
    import {ref, computed, watch, onMounted} from 'vue'
    import Person from '@/domain/person'
    import isValidationError from '@/http/is_validation_error'
    import {getLookups, lookups} from '@/forms/profile_form';
    import ProfilePhotoForm from '@/components/people/ProfilePhotoForm.vue';
    import { hasPermission, userIsPerson } from '../../auth_utils';
    import InstitutionSearchSelect from '../forms/InstitutionSearchSelect.vue';
    import AddressInput from '../forms/AddressInput.vue';
    import TimezoneSearchSelect from '../forms/TimezoneSearchSelect.vue';
    import CredentialsInput from '../forms/CredentialsInput.vue';
    import ExpertisesInput from '../forms/ExpertisesInput.vue';

    const store = useStore();
    const props = defineProps({
                    person: {
                        type: Object,
                        required: true
                    },
                    showTitle: {
                        type: Boolean,
                        default: true
                    },
                    allowCancel: {
                        type: Boolean,
                        default: true,
                    },
                    saveButtonText: {
                        type: String,
                        default: 'Save'
                    }
                });

    const emits = defineEmits([
                        'saved',
                        'canceled'
                    ]);

    const errors = ref({});
    const profile = ref({});

    const initProfile = () => {
        if (props.person.clone) {
            profile.value = {...props.person.attributes};
        } else {
            profile.value = {...props.person};
        }
        profile.value.credential_ids = props.person.credentials ? props.person.credentials.map(c => c.id) : null;
    };

    const save = async () => {
        try {
            if (profile.value.credentials) {
                profile.value.credential_ids = profile.value.credentials.map(c => c.id)
            }
            if (profile.value.expertises) {
                profile.value.expertise_ids = profile.value.expertises.map(c => c.id)
            }
            const updatedPerson = await store.dispatch(
                    'people/updateProfile',
                    {uuid: props.person.uuid, attributes: profile.value}
                ).then(rsp => {
                    store.dispatch('getCurrentUser', {force: true})
                    store.commit('pushSuccess', 'Your profile has been updated.')
                    return rsp.data;
                })

                errors.value = {};
                emits('saved', new Person(updatedPerson));
        } catch (error) {
            if (isValidationError(error)) {
                errors.value = error.response.data.errors;
            }
        }
    };

    const cancel = () => {
        initProfile();
        errors.value = {};
        emits('canceled');
    };

    const canEditAllFields = computed(() => hasPermission('people-manage') || userIsPerson(props.person));

    watch(() => props.person, () => {
        initProfile()
    }, {immediate: true});

    onMounted(() => {
        getLookups();
    });

</script>

<template>
    <div>
        <h3 v-if="showTitle">Profile</h3>
        <div class="float-right" v-if="(hasPermission('people-manage') || userIsPerson(person))"
        >
            <profile-photo-form :person="person" style="width: 100px; height: 100px;" />
        </div>

        <input-row label="First Name"
            v-model="profile.first_name"
            :errors="errors.first_name"
        />

        <input-row label="Last Name"
            v-model="profile.last_name"
            :errors="errors.last_name"
        />
        <input-row label="Email"
            v-model="profile.email"
            :errors="errors.email"
        />

        <input-row label="Institution"
        :errors="errors.institution_id">
            <InstitutionSearchSelect v-model="profile.institution_id" />
        </input-row>

        <input-row
            :errors="errors.credential_ids">
            <template v-slot:label>
                Credentials
                <note>Degrees and Certifications</note>
            </template>
            <CredentialsInput v-model="profile.credentials"></CredentialsInput>
            <!-- <template  v-slot:after-input>
                <note>Include degrees and certifications such as PhD, MD, CGC, etc. Please include professional roles and associations in your biography.</note>
            </template> -->
        </input-row>

        <input-row
            :errors="errors.expertise_ids">
            <template v-slot:label>
                Expertise
            </template>
            <ExpertisesInput v-model="profile.expertises" />
        </input-row>


        <input-row v-if="canEditAllFields" label="Biography"
            v-model="profile.biography"
            :errors="errors.biography"
            type="large-text"
        />

        <input-row label="Address" v-if="canEditAllFields">
            <AddressInput v-model="profile" :errors="errors" />
        </input-row>

        <input-row v-if="canEditAllFields" label="Country"
            v-model="profile.country_id"
            type="select"
            :options="lookups.countries"
            :errors="errors.country_id"
        />

        <input-row v-if="canEditAllFields" label="Phone"
            v-model="profile.phone"
            :errors="errors.phone"
        />

        <input-row v-if="canEditAllFields" label="Timezone" :errors="errors.timezone">
            <TimezoneSearchSelect v-model="profile.timezone"></TimezoneSearchSelect>
        </input-row>

        <hr class="my-4">

        <button-row @submitted="save" @canceled="cancel()" :submit-text="saveButtonText" :show-cancel="allowCancel"></button-row>
    </div>
</template>

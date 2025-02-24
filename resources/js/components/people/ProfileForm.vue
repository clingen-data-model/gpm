<script setup>
    import {useStore} from 'vuex'
    import {ref, computed, watch, onMounted} from 'vue'
    import Person from '@/domain/person'
    import isValidationError from '@/http/is_validation_error'
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
    const saving = ref(false);

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
            saving.value = true;
            const updatedPerson = await store.dispatch(
                    'people/updateProfile',
                    {uuid: props.person.uuid, attributes: profile.value}
                ).then(rsp => {
                    store.dispatch('getCurrentUser', {force: true})
                    store.commit('pushSuccess', 'Your profile has been updated.')
                    return rsp.data;
                })

                saving.value = false;
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

    const countries = computed(() => {
        return store.getters['countries/items'].map(i => ({value: i.id, label: i.name}))
    })

    onMounted(() => {
        store.dispatch('countries/getItems');
    });

</script>

<template>
    <div>
        <h3 v-if="showTitle">Profile</h3>
        <div class="float-right" v-if="(hasPermission('people-manage') || userIsPerson(person))"
        >
            <ProfilePhotoForm :person="person" style="width: 100px; height: 100px;" />
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
                <div>
                    <popover hover arrow>
                        <div class="text-xs cursor-pointer text-blue-600">What is this?</div>
                        <template v-slot:content>
                            <div style="max-width: 500px">
                                <h4>What do you mean by credentials?</h4>
                                <p>
                                    Credentials are any degrees or professional certifications you have earned.  Typically, these will be the acronyms you list after your name on your CV.
                                </p>
                                <p>
                                    We recommend choosing one or more options already in the list of credentials, but if you do not see yours feel free to add it.
                                </p>
                                <p>
                                    Please do not include current job titles or specific.  If you would like to add that information to your profile please do so in the biography field below.
                                </p>
                            </div>
                        </template>
                    </popover>
                </div>
            </template>
            <CredentialsInput v-model="profile.credentials"></CredentialsInput>
        </input-row>

        <input-row
            :errors="errors.expertise_ids">
            <template v-slot:label>
                Area of Expertise
                <div>
                    <popover hover arrow>
                        <div class="text-xs cursor-pointer text-blue-600">What is this?</div>
                        <template v-slot:content>
                            <div style="max-width: 500px">
                                <h4>What do you mean by <em>Area of Expertise</em>?</h4>
                                <p>
                                    We use this information to determine if the make up of gene and variant expert panels includes the breadth of expertise needed to do it's work.
                                </p>
                                <p>
                                    Please choose the option from list that best describes your area of expertise. If you don't feel any of these options fit, select 'None'.
                                </p>
                            </div>
                        </template>
                    </popover>
                </div>
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
            :options="countries"
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

        <div v-if="saving" class="mb-2">Saving...</div>
        <button-row v-if="!saving"
            @submitted="save"
            @canceled="cancel()"
            :submit-text="saveButtonText"
            :show-cancel="allowCancel"
        />
    </div>
</template>

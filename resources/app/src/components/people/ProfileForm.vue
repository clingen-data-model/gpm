<script setup>
    import {useStore} from 'vuex'
    import {ref, computed, watch, onMounted} from 'vue'
    import isValidationError from '@/http/is_validation_error'
    import {getLookups, lookups} from '@/forms/profile_form';
    import ProfilePhotoForm from '@/components/people/ProfilePhotoForm.vue';
    import { hasPermission, userIsPerson } from '../../auth_utils';
    import InstitutionSearchSelect from '../forms/InstitutionSearchSelect.vue';
    import AddressInput from '../forms/AddressInput.vue';
    import TimezoneSearchSelect from '../forms/TimezoneSearchSelect.vue';
    import SearchSelect from '../forms/SearchSelect.vue';
    import {api} from '@/http'

    const store = useStore();
    const props = defineProps({
                    person: {
                        type: Object,
                        required: true
                    }
                });
    const emits = defineEmits([
                        'saved',
                        'canceled'
                    ]);

    const errors = ref({});
    const profile = ref({});

    const initProfile = () => {
        profile.value = {...props.person.attributes};
        profile.value.credential_ids = props.person.credentials ? props.person.credentials.map(c => c.id) : null;
        console.log(profile.value)
    };

    const save = async () => {
        try {
            if (profile.value.credential_ids) {
                profile.value.credential_ids = profile.value.credentials.map(c => c.id)
            }
            await store.dispatch(
                    'people/updateProfile',
                    {uuid: props.person.uuid, attributes: profile.value}
                )
                .then(() => {
                    store.dispatch('getCurrentUser', {force: true})
                    store.commit('pushSuccess', 'Your profile has been updated.')
                })

            errors.value = {};
            emits('saved');
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

    const canEditAllFields = computed(() => hasPermission('people-manage') || userIsPerson(this.person));

    const credentials = ref([]);
    const getCredentials = async () => {
        credentials.value = await api.get('/api/credentials')
                                .then(rsp => rsp.data);
    }

    watch(() => props.person, () => {
        initProfile()
    }, {immediate: true});

    onMounted(() => {
        getLookups();
        getCredentials();
    });

    const searchCredentials = async(keyword, options) => {
        return options.filter(o => {
            const pattern = /[.,-]/g
            const normedKeyword = keyword.replace(pattern, '').toLowerCase();
            return o.name.toLowerCase().match(normedKeyword)
                || o.synonyms.some(s => s.name.toLowerCase().match(normedKeyword));
        })
    }

</script>

<template>
    <div>
        <h3>Profile</h3>
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
            <SearchSelect
                v-model="profile.credentials"
                :options="credentials"
                :multiple="true"
                showOptionsOnFocus
                :searchFunction="searchCredentials"
            >
                <template v-slot:fixedBottomOption>
                    <div class="text-sm">
                        Don't see your credential? <button class="link">Create a new one.</button>
                    </div>
                </template>
            </SearchSelect>
            <template  v-slot:after-input>
                <note>Include degrees and certifications such as PhD, MD, CGC, etc. Please include professional roles and associations in your biography.</note>
            </template>
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

        <button-row @submitted="save" @canceled="cancel()" submit-text="Save"></button-row>
    </div>
</template>

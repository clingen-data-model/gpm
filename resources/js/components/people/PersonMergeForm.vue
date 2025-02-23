<template>
    <div>
        <p class="mb-8">
            Merging peple will do the following:
            <ol class="list-decimal ml-6">
                <li>Add the authoritative person to all groups of which the obsolete person is a member.</li>
                <li>Delete the obsolete person, their memberships, their invite, and their user account (if activated).</li>
            </ol>
        </p>
        <input-row label="Merge" vertical>
            <template v-slot:label>
                Merge <note class="inline-block">(Obsolete person that will be deleted)</note>
            </template>
            <person-search-select v-model="obsoleteCopy" :allow-add="false"></person-search-select>
            
        </input-row>
        <input-row label="Into" :errors="errors.authority_id" vertical>
            <template v-slot:label>Into <note class="inline">(Authoritative person)</note></template>
            <person-search-select v-model="authorityCopy" :allow-add="false"></person-search-select>
        </input-row>
        <button-row submit-text="Merge" @submitted="commitMerge" @canceled="cancelMerge" />
    </div>
</template>
<script>
import { isValidationError } from '@/http';

export default {
    name: 'PersonMergeForm',
    props: {
        authority: {
            type: Object,
            required: false
        },
        obsolete: {
            type: Object,
            default: () => ({})
        }
    },
    emits: [
        'saved',
        'canceled'
    ],
    data() {
        return {
            authorityCopy: null,
            obsoleteCopy: null,
            errors: {}
        }
    },
    watch: {
        authority: {
            immediate: true,
            handler (to) {
                if (!to) {
                    return;
                }
                this.authorityCopy = {...to.attributes}
            }
        },
        obsolete: {
            immediate: true,
            handler (to) {
                if (!to) {
                    return;
                }
                this.obsoleteCopy = {...to.attributes};
            }
        }
    },
    methods: {
        async commitMerge () {
            try {
                this.errors = {};
                await this.$store.dispatch(
                    'people/mergePeople', 
                    {
                        authority: this.authorityCopy, 
                        obsolete: this.obsoleteCopy
                    }
                );
                this.authorityId = null;
                this.obsoleteIds = null;
                this.$emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        cancelMerge () {
            this.authorityId = null;
            this.obsoleteId = null;
            this.$emit('canceled');
        }
    }
}
</script>
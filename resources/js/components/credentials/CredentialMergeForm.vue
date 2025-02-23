<script>
import SearchSelect from '@/components/forms/SearchSelect.vue'
import { isValidationError } from '@/http';

export default {
    name: 'CredentialMergeForm',
    components: {
        SearchSelect
    },
    props: {
        obsoletes: {
            type: Array,
            default: () => []
        },
        credentials: {
            type: Array,
            default: () => []
        }
    },
    emits: [
        'saved',
        'canceled'
    ],
    data() {
        return {
            selectedAuthority: null,
            selectedObsolete: null,
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
                this.authorityId = to.id
            }
        },
        obsoletes: {
            immediate: true,
            deep: true,
            handler (to) {
                this.handleObsoleteUpdate(to)
            }
        }
    },
    methods: {
        handleObsoleteUpdate (value) {
            this.selectedObsolete = value;
        },
        async commitMerge () {
            console.log('committing merge', this.selectedAuthority, this.selectedObsolete)
            if (!this.selectedAuthority || !this.selectedObsolete) {
                return;
            }
            try {
                this.errors = {};
                await this.$store.dispatch(
                        'credentials/merge',
                        {
                            authority: this.selectedAuthority,
                            obsolete: this.selectedObsolete
                        }
                );
                this.selectedAuthority = null;
                this.selectedObsolete = null;
                this.$emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        cancelMerge () {
            this.authorityId = null;
            this.obsoleteIds = [];
            this.$emit('canceled');
        }
    }
}
</script>
<template>
    <div>
        <p class="mb-8">
            Merging credentials will do the following:
            <ol class="list-decimal ml-6">
                <li>Transfer all people with the obsolete credential to the authoritiative credential.</li>
                <li>Delete the obsolete credential.</li>
            </ol>
        </p>
        <input-row label="Merge" vertical>
            <template v-slot:label>
                Merge <note class="inline-block">(Obsolete credential that will be deleted)</note>
            </template>

            <SearchSelect
                v-model="selectedObsolete"
                :options="credentials"
                :showOptionsOnFocus="true"
                keyBy="id"
            />
        </input-row>
        <input-row label="Into" :errors="errors.authority_id" vertical>
            <template v-slot:label>Into <note class="inline">(Authoritative credential)</note></template>
            <SearchSelect
                v-model="selectedAuthority"
                :options="credentials"
                :showOptionsOnFocus="true"
                keyBy="id"
            />
        </input-row>
        <button-row submit-text="Merge" @submitted="commitMerge" @canceled="cancelMerge" />
    </div>
</template>

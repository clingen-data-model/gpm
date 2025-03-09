<script>
import {mergeInstitutions} from '@/forms/institution_form'
import { isValidationError } from '@/http';

export default {
    name: 'InstitutionMergeForm',
    props: {
        authority: {
            type: Object,
            required: false
        },
        obsoletes: {
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
            authorityId: null,
            obsoleteIds: [],
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
                this.obsoleteIds = to.map(o => o.id);
            }
        }
    },
    methods: {
        handleObsoleteUpdate (value) {
            if (!value) {
                this.obsoleteIds = [];
                return;
            }
            this.obsoleteIds = [value];
        },
        async commitMerge () {
            try {
                this.errors = {};
                await mergeInstitutions(this.authorityId, this.obsoleteIds);
                this.authorityId = null;
                this.obsoleteIds = [];
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
      Merging institutions will do the following:
      <ol class="list-decimal ml-6">
        <li>Transfer all people with the obsolete institution to the authoritiative institution.</li>
        <li>Delete the obsolete institution.</li>
      </ol>
    </p>
    <input-row label="Merge" vertical>
      <template #label>
        Merge <note class="inline-block">
          (Obsolete institution that will be deleted)
        </note>
      </template>
      <institution-search-select :model-value="obsoleteIds" :allow-add="false" @update:modelValue="handleObsoleteUpdate" />
    </input-row>
    <input-row label="Into" :errors="errors.authority_id" vertical>
      <template #label>
        Into <note class="inline">
          (Authoritative institution)
        </note>
      </template>
      <institution-search-select v-model="authorityId" :allow-add="false" />
    </input-row>
    <button-row submit-text="Merge" @submitted="commitMerge" @canceled="cancelMerge" />
  </div>
</template>
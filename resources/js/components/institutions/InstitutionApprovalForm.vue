<script>
import {markApproved} from '@/forms/institution_form'

export default {
    name: 'ComponentName',
    props: {
        modelValue: {
            type: Object,
            required: true
        }
    },
    emits: [
        'saved',
        'canceled',
        'update:modelValue'
    ],
    methods: {
        async approve () {
            const updatedInst = await markApproved(this.modelValue);
            this.$emit('update:modelValue', updatedInst);
            this.$emit('saved', updatedInst)
        },
        cancelApproval () {
            this.$emit('canceled')
        }
    }
}
</script>
<template>
  <div>
    Are you sure you want to approve this institution?
    <dictionary-row label="Name">
      {{ modelValue.name }}
    </dictionary-row>
    <dictionary-row label="Abbreviation">
      {{ modelValue.abbreviation || '--' }}
    </dictionary-row>
    <dictionary-row label="URL">
      {{ modelValue.url || '--' }}
    </dictionary-row>
    <dictionary-row label="Country">
      {{ modelValue.country ? modelValue.country.name : '--' }}
    </dictionary-row>
    <dictionary-row label="Reportable">
      {{ modelValue.reportable ? 'Yes' : 'No' }}
    </dictionary-row>
    <button-row submit-text="Approve" @submitted="approve" @cancel="cancelApproval" />
  </div>
</template>
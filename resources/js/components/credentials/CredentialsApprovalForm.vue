<script>
import {api} from '@/http'

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
            const updatedCredential = await api.put(`/api/credentials/${this.modelValue.id}`, {name: this.modelValue.name, approved: 1})
                .then ( response => response.data);
            this.$emit('update:modelValue', updatedCredential);
            this.$emit('saved', updatedCredential)
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
    <button-row submit-text="Approve" @submitted="approve" @cancel="cancelApproval" />
  </div>
</template>

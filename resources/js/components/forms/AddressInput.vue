<script>
import mirror from '@/composables/setup_working_mirror'
import InputErrors from '@/components/forms/InputErrors.vue'

export default {
    name: 'AddressInput',
    components: {
        InputErrors
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        }
    },
    emits: [
        ...mirror.emits
    ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);

        return {
            workingCopy
        }
    },
    computed: {
        addressErrors () {
            const addressErrors = [];
            ['city', 'state', 'zip'].forEach(field => {
                if (this.errors[field]) {
                    addressErrors.push(...this.errors[field]);
                }
            });
            return addressErrors;
        },
    }
}
</script>
<template>
    <div class="w-full space-y-1">
        <input v-model="workingCopy.street1" type="text" placeholder="street 1" class="w-full">
        <InputErrors class="text-xs" :errors="errors.street1 || []"></InputErrors>
        <input v-model="workingCopy.street2" type="text" placeholder="street 2" class="w-full">
        <InputErrors class="text-xs" :errors="errors.street2 || []"></InputErrors>

        <div class="flex space-x-1">
            <input v-model="workingCopy.city" type="text" placeholder="City" class="w-1/3 shrink">
            <input v-model="workingCopy.state" type="text" placeholder="State" class="w-1/3 shrink">
            <input v-model="workingCopy.zip" type="text" placeholder="Postal Code" class="w-1/3 shrink">
        </div>
        <InputErrors class="text-xs" :errors="addressErrors"></InputErrors>
    </div>
</template>
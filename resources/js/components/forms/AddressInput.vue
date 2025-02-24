<template>
    <div class="w-full space-y-1">
        <input type="text" v-model="workingCopy.street1" placeholder="street 1" class="w-full">
        <InputErrors class="text-xs" :errors="errors.street1 || []"></InputErrors>
        <input type="text" v-model="workingCopy.street2" placeholder="street 2" class="w-full">
        <InputErrors class="text-xs" :errors="errors.street2 || []"></InputErrors>

        <div class="flex space-x-1">
            <input type="text" v-model="workingCopy.city" placeholder="City" class="w-1/3 shrink">
            <input type="text" v-model="workingCopy.state" placeholder="State" class="w-1/3 shrink">
            <input type="text" v-model="workingCopy.zip" placeholder="Postal Code" class="w-1/3 shrink">
        </div>
        <InputErrors class="text-xs" :errors="addressErrors"></InputErrors>
    </div>
</template>
<script>
import InputErrors from '@/components/forms/InputErrors.vue'
import mirror from '@/composables/setup_working_mirror'

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
    },
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);

        return {
            workingCopy
        }
    }
}
</script>
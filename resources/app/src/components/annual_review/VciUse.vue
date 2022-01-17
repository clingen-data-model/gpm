<template>
    <div>
        <input-row 
            label="Please Indicate whether the Expert Panel uses the VCI for all variant curation activities, or if the Expert Panel intends to use the VCI once they begin curation. If not used for all activities, please describe."
            :errors="errors.vci_use"
            vertical
        >
            <div class="ml-4">
                <radio-button v-model="workingCopy.vci_use" value="yes">
                    Yes, use the VCI for all curation activities or intend to once curation begins.
                </radio-button>
                <radio-button v-model="workingCopy.vci_use" value="no">
                    No
                </radio-button>
                <transition name="slide-fade-down">            
                    <input-row v-if="workingCopy.vci_use == 'no'" :errors="errors.vci_use_details" label="Please explain" vertical class="ml-4">
                        <textarea rows="5" class="w-full" v-model="vci_use_details"></textarea>
                    </input-row>
                </transition>
            </div>
        </input-row>
    </div>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'VciUse',
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [ ...mirror.emits ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>
<template>
    <div>
        <slot name="document">
            <div>
                <h3 class="text-lg font-bold mb-1">{{documentName}}</h3>
                <document-manager 
                    :application="application"
                    :document-type-id="documentType"
                    :getsReviewd="documentGetsReviewed"
                ></document-manager>
            </div>
        </slot>

        <slot name="approve-button">
            <hr class="my-6 text">

            <button class="btn btn-lg w-full" @click="approveStep">{{approveButtonLabel}}</button>

            <hr class="my-6 text">
        </slot>


        <slot></slot>

        <slot name="log">
        <div class="mb-6">
            <h4 class="text-md font-bold mb-2">Step {{step}} Progress Log</h4>
            <application-log :step="step"></application-log>
        </div>a
        </slot>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import ApplicationLog from './ApplicationLog'
import DocumentManager from './DocumentManager'

export default {
    components: {
        ApplicationLog,
        DocumentManager
    },
    props: {
        step: {
            type: Number,
            required: true
        },
        documentName: {
            type: String,
            required: false,
            default: 'Set a document-type attribute if you don\'t use the "document" slot'
        },
        documentType: {
            type: Number,
            required: false,
            default: 1
        },
        documentGetsReviewed: {
            type: Boolean,
            required: false,
            default: true
        },
        approveButtonLabel: {
            type: String,
            required: false,
            default: 'Set "approve-button-label" if not overriding slot "approve-button"'
        }
    },
    emits: ['documentUploaded', 'stepApproved'],
    computed: {
        ...mapGetters({
            application: 'currentItem'
        })
    },
    methods: {
        approveStep () {
            throw Error('not implemented')
        }
    }
}
</script>
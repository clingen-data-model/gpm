<script>
import is_validation_error from '../../../http/is_validation_error';
export default {
    name: 'DocumentEditForm',
    props: {
        document: {
            required: true,
            type: Object
        },
        application: {
            required: true,
            type: Object
        }
    },
    emits: [
        'triggermarkreviewed',
        'updated',
        'saved'
    ],
    data() {
        return {
            docProxy: {},
            errors: {}
        }
    },
    computed: {
        type() {
            return this.document.type ? this.document.type.long_name : ''
        }
    },
    watch: {
        document: {
            immediate: true,
            handler () {
                this.docProxy = JSON.parse(JSON.stringify(this.document))
            }
        }
    },
    methods: {
        cancel() {
            this.docProxy = {};
            this.$emit('canceled');
        },
        async save() {
            try {
                await this.$store.dispatch('applications/updateDocumentInfo', {application: this.application, document: this.docProxy});
                this.docProxy = {};
                this.$emit('saved');
                this.$emit('updated');
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                }
                throw error
            }
        }
    }
}
</script>
<template>
    <form-container>
        <h2 class="text-lg border-b pb-1 mb-3">Edit {{type}} version {{document.version}} Info</h2>
        <dictionary-row label="File">
            {{document.filename}}
        </dictionary-row>
        <input-row v-model="docProxy.date_received" label="Date Received" type="date" :errors="errors.date_received"></input-row>
        <input-row :errors="errors.notes" label="Notes">
            <textarea name="notes" v-model="docProxy.notes" cols="30" rows="10"></textarea>
        </input-row>
        <button-row>
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Save</button>
        </button-row>
    </form-container>
</template>
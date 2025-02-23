<template>
    <div>
        <button class="btn btn-xs" @click="showModal = true">Upload COI file</button>
        <modal-dialog v-model="showModal">
            <h2>Upload a legacy COI file</h2>
            <input-row label="COI File" :errors="errors.file">
                <input type="file" ref="fileInput">
            </input-row>
            <button-row @submitClicked="save" @cancelClicked="cancel" submit-text="Save"></button-row>
        </modal-dialog>
    </div>
</template>
<script>
import Application from '@/domain/application'
import is_validation_error from '@/http/is_validation_error'

export default {
    props: {
        application: {
            type: Application,
            required: true
        }
    },
    emits: [
        'saved',
        'canceled',
        'done'
    ],
    data() {
        return {
            showModal: false,
            newCoi: {
                email: null,
                first_name: null,
                last_name: null
            },
            errors: {}
        }
    },
    computed: {
        nameErrors() {
            const errors = []
            if (this.errors.first_name) {
                errors.push(this.errors.first_name)
            } 
            if (this.errors.last_name) {
                errors.push(this.errors.last_name)
            } 
            return errors;
        }
    },
    methods: {
        async save() {
            try {
                const data = this.assembleFormData(this.$refs['fileInput'], this.newCoi);

                await this.$store.dispatch('storeLegacyCoi', {application: this.application, coiData: data})
                this.clearForm();
                this.showModal = false;
                this.$emit('saved');
                this.$emit('done');
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        cancel() {
            this.clearForm();
            this.showModal = false;
            this.$emit('canceled');
        },
        clearForm() {
            this.newCoi = {
                email: null,
                first_name: null,
                last_name: null
            };
            this.errors = {}
        }
    },
    setup() {
        const assembleFormData = (fileInput, otherData) => {
            const data = new FormData();
            Object.keys(otherData)
                .forEach(key => {
                    const val = otherData[key]
                    if (val == null) return;
                    data.append(key, val);
                })
            data.append('file', fileInput.files[0]);
            data.append('document_type_id', 6);
            return data;
        }

        return {assembleFormData}

    }
}
</script>
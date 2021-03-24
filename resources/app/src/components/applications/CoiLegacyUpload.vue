<template>
    <div>
        <button class="btn btn-xs" @click="showModal = true">Upload COI file</button>
        <modal-dialog v-model="showModal">
            <h4 class="text-lg">Upload a legacy COI file</h4>
            <input-row label="Name" type="text" :errors="nameErrors">
                 <input type="text" v-model="newCoi.first_name" placeholder="First">
                 &nbsp;
                 <input type="text" v-model="newCoi.last_name" placeholder="Last">
            </input-row>
            <input-row label="Email" type="email" v-model="newCoi.email" placeholder="email@example.com" :errors="errors.email"></input-row>
            <input-row label="COI File" :errors="errors.file">
                <input type="file" ref="fileInput">
            </input-row>
            <button-row @submitClicked="save" @cancelClicked="cancel" submit-text="Save"></button-row>
        </modal-dialog>
    </div>
</template>
<script>
import Application from '../../domain/application'
import api from '../../http/api'

export default {
    props: {
        application: {
            type: Application,
            required: true
        }
    },
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
            const data = this.assembleFormData(this.$refs['fileInput'], this.newCoi);
            api.post(`/api/applications/${this.application.uuid}`, data)
        },
        cancel() {
            this.clearForm();
            this.showModal = false;
        },
        clearForm() {
            
        }
    },
    setup() {
        const assembleFormData = (fileInput, otherData) => {
            let data = new FormData();
            Object.keys(otherData)
                .forEach(key => {
                    const val = otherData[key]
                    if (val == null) return;
                    data.append(key, val);
                })
            data.append('file', fileInput.files[0]);
            return data;
        }

        return {assembleFormData}

    }
}
</script>
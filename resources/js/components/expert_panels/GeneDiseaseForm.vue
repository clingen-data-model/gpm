<script>
import is_validation_error from '../../http/is_validation_error'
export default {
    name: 'GeneDiseaseForm',
    props: {
        gene: {
            required: false,
            type: Object,
            default: () => ({})
        }
    },
    data() {
        return {
            errors: {},
            hgncId: null,
            mondoId: null,
        }
    },
    computed: {

    },
    methods: {
         async save () {
            try {
                //save
                this.clearForm();
                this.goBack();
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.errors;
                    return;
                }
                throw error
            }
        },
        cancel () {
            this.clearForm();
            this.goBack();
        },
        goBack () {
            this.$router.go(-1);
        },
        clearForm () {
            this.hgncId = null;
            this.mondoId = null;
        }
    }
}
</script>
<template>
    <div>
        <input-row label="HGNC Symbol" :errors="errors.hgncId">
            <input type="text" v-model="hgncId">
        </input-row>
        <input-row label="Disease" :errors="errors.mondo_id">
            <input type="text" v-model="mondoId">
        </input-row>
        <button-row @submit="save" @cancel="cancel" submit-text="Save"></button-row>
    </div>
</template>
<script>
import { isValidationError } from '@/http';
import {mapState} from 'vuex'
export default {
    name: 'DocumentEditForm',
    props: {
        document: {
            required: true,
            type: Object
        },
        saveFunction: {
            required: true,
            type: Function
        }
    },
    emits: [
        'saved',
        'canceled',
    ],
    data() {
        return {
            docProxy: {},
            errors: {}
        }
    },
    computed: {
        ...mapState({
            documentTypes: state => state.doctypes.items
        }),
    },
    watch: {
        document: {
            immediate: true,
            handler () {
                this.docProxy = JSON.parse(JSON.stringify(this.document))
            }
        }
    },
    mounted () {
        this.$store.dispatch('doctypes/getItems');
    },
    methods: {
        cancel() {
            this.docProxy = {};
            this.$emit('canceled');
        },
        async save() {
            try {
                this.saveFunction(this.docProxy);
                this.$emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
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
    <dictionary-row label="File">
      {{ document.filename }}
    </dictionary-row>
    <input-row label="Type" :errors="errors.document_type_id">
      <select v-model="docProxy.document_type_id">
        <option v-for="type in documentTypes" :key="type.id" :value="type.id">
          {{ type.long_name }}
        </option>
      </select>
    </input-row>
    <input-row :errors="errors.notes" label="Notes">
      <textarea v-model="docProxy.notes" name="notes" cols="30" rows="10" />
    </input-row>
    <button-row>
      <button class="btn" @click="cancel">
        Cancel
      </button>
      <button class="btn blue" @click="save">
        Save
      </button>
    </button-row>
  </form-container>
</template>
<style lang="postcss">
    /* Note: punting on more precise sizing b/c it's more complicated than it should be. */
    .ck-editor__editable, .ck-editor__editable_inline {
        min-width: 300px;
        max-width: 500px;
        height: 300px;
    }
</style>
<template>
    <div>
        <ckeditor 
            :editor="editor" 
            :model-value="modelValue" 
            :config="editorConfig"
            @keyup.enter.stop=""
            @input="emitValue"
            class="w-full"
        ></ckeditor>
    </div>
</template>
<script>
import CKEditor from '@ckeditor/ckeditor5-build-classic'

export default {
    name: 'RichTextEditor',
    props: {
        modelValue: {
            type: String,
            required: false,
            default: ''
        },
    },
    data() {
        return {
            editor: CKEditor,
            editorConfig: {
                toolbar: ['bold', 'italic', '|', 'link'],
                remove: ['Heading', "CKFinderUploadAdapter"],
                width: '100%',
                height: 200,
                resize_minWidth: '300px',
                resize_maxWidth: '300px',
            }            
        }
    },
    computed: {

    },
    methods: {
        emitValue(evt) {
            this.$emit('update:modelValue', evt)
        },
    }
}
</script>
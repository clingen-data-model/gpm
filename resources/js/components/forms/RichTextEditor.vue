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
                // resize_minWidth: '300px',
                resize_maxWidth: '700px',
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
<template>
    <div>
        <ckeditor 
            :editor="editor" 
            :model-value="modelValue" 
            :config="editorConfig"
            @input="emitValue"
        ></ckeditor>
    </div>
</template>
<style>
    /* Note: punting on more precise sizing b/c it's more complicated than it should be. */
    .ck-editor__editable, .ck-editor__editable_inline {
        min-width: 300px;
        height: 250px;
    }
    .ck.ck-editor__main p, .ck.ck-editor__main ul {
        @apply mb-4;
    }

    .ck.ck-editor__main ul > li{
        @apply list-disc ml-4;
    }

    .ck.ck-editor__main a {
        @apply text-blue-600 underline;
    }
</style>
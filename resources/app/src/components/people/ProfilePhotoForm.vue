<template>
    <div>
        <img 
            :src="srcPath" 
            :alt="altText" 
            
            @click="initUpload"
        >
        <button 
            @click="initUpload"
            class="border border-t-0 bg-gray-200 block w-full rounded-b"
        >
            Edit
        </button>

        <teleport to='body'>
            <modal-dialog v-model="showForm" title="Upload a new profile photo">
                    <img 
                        :src="previewPath" 
                        alt="Preview" 
                        style="width: 200px; height: 200px" 
                        class=" border rounded-t"
                        @click="initUpload"
                    >
                    <input type="file" @change="setFile" ref="fileInput">
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>

export default {
    name: 'ProfilePhotoForm',
    props: {
        person: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            showForm: false,
            file: null
        }
    },
    computed: {
        srcPath () {
            return this.person.profile_photo_path 
                ? `/profile-photos/${this.person.profile_photo_path}` 
                : `/profile-photos/standin.png`;
        },
        altText () {
            return this.person.profile_photo_path 
                ? `${this.person.name} profile photo.` 
                : 'Default profile image';
        },
        previewPath () {
            return this.file
                ? URL.createObjectURL(this.file)
                : this.srcPath
        }
    },
    methods: {
        initUpload () {
            this.showForm = true;
        },
        setFile () {
            this.file = this.$refs.fileInput.files[0]
        }
    }
}
</script>
<template>
    <div>
        <div class="border border-gray-200 rounded-lg">
            <img 
                :src="srcPath" 
                :alt="altText"
                style="max-width: 100px"
                class="rounded-t-lg"
                @click="initUpload"
            >
            <button 
                @click="initUpload"
                class="border border-t-0 bg-gray-200 block w-full"
            >
                Edit
            </button>
        </div>

        <teleport to='body'>
            <modal-dialog v-model="showForm" title="Upload a new profile photo">
                <ImageCropper 
                    :image-src="srcPath" 
                    @cropped="setCroppedImage"
                />

                <input-row hideLabel :errors="errors.profile_photo">
                    <input type="file" @change="setFile" ref="fileInput">
                </input-row>


                <button-row submit-text="Save" @submitted="saveCropped" @canceled="cancelCropped" />
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import ImageCropper from '@/components/ImageCropper'
import {api, isValidationError} from '@/http'

export default {
    name: 'ProfilePhotoForm',
    components: {
        ImageCropper
    },
    props: {
        person: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            showForm: false,
            file: null,
            croppedImage: null,
            errors: {}
        }
    },
    watch: {
        person: {
            immediate: true,
            deep: true,
            handler (to) {
                if (to.profile_photo) {
                    this.fetchProfileImage();
                }
            }
        }
    },
    computed: {
        srcPath () {
            if (this.file) {
                console.log('create from this.file');
                return URL.createObjectURL(this.file);
            }
            if (this.person.profile_photo) {
                return `/profile-photos/${this.person.profile_photo}`
            }
            return null;
        },
        profilePhoto () {
            if (this.person.profile_photo) {
                return `/profile-photos/${this.person.profile_photo}`
            }
            return null;
        },
        altText () {
            return this.person.profile_photo_path 
                ? `${this.person.name} profile photo.` 
                : 'Default profile image';
        },
    },
    methods: {
        initUpload () {
            this.showForm = true;
            this.$nextTick(() => {
                window.dispatchEvent(new Event('resize'));
            });
        },
        setFile () {
            this.file = this.$refs.fileInput.files[0]
        },
        setCroppedImage (blob) {
            console.log('setCroppedImage', blob);
            this.croppedImage = blob;
        },
        async fetchProfileImage () {
            const url = `/api/people/${this.person.uuid}/profile-photo`;
            const options = {
                Accept: 'application/octet-stream',
                timeout: 30000,
                responseType: 'blob'
            };
            this.file = await api.get(url, options)
                            .then(rsp => {
                                if( !(rsp.data instanceof Blob)) return;

                                return new Blob([rsp.data]);
                            });
        },
        async saveCropped () {
            const formData = new FormData();
            formData.append('profile_photo', this.croppedImage);

            api.post(`/api/people/${this.person.uuid}/profile-photo`, formData)
                .then(() => {
                    this.$store.dispatch('people/getPerson', {uuid: this.person.uuid});
                    this.showForm = false;
                    this.file = null;
                })
                .catch(error => {
                    if (isValidationError(error)) {
                        this.errors = error.response.data.errors;
                    }
                });
        },
        cancelCropped () {
            this.showForm = false;
            this.file = null;
        }
    }
}
</script>
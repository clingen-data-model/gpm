<script>
import ImageCropper from '@/components/ImageCropper.vue'
import { api, isValidationError } from '@/http'

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
            croppedImageBlob: null,
            errors: {},
            saving: false
        }
    },
    watch: {
        person: {
            immediate: true,
            deep: true,
            handler(to) {
                if (to.profile_photo) {
                    this.fetchProfileImage();
                }
            }
        }
    },
    computed: {
        srcPath() {
            if (this.file) {
                return URL.createObjectURL(this.file);
            }
            if (this.person.profile_photo) {
                return `/profile-photos/${this.person.profile_photo}`
            }
            return '/images/default_profile.jpg';
        },
        profilePhoto() {
            if (this.person.profile_photo) {
                return `/profile-photos/${this.person.profile_photo}`
            }
            return '/images/default_profile.jpg';
        },
        altText() {
            return this.person.profile_photo_path
                ? `${this.person.name} profile photo.`
                : 'Default profile image';
        },
    },
    methods: {
        initUpload() {
            this.showForm = true;
            this.$nextTick(() => {
                window.dispatchEvent(new Event('resize'));
            });
        },
        setFile() {
            this.file = this.$refs.fileInput.files[0]
        },
        setCroppedImageBlob(blob) {
            this.croppedImageBlob = blob;
        },
        async fetchProfileImage() {
            const url = `/api/people/${this.person.uuid}/profile-photo`;
            const options = {
                Accept: 'application/octet-stream',
                timeout: 30000,
                responseType: 'blob'
            };
            this.file = await api.get(url, options)
                .then(rsp => {
                    if (!(rsp.data instanceof Blob)) return;

                    return new Blob([rsp.data]);
                });
        },
        saveCropped() {
            if (this.croppedImageBlob.size > 1000000) {
                const width = 400;
                createImageBitmap(this.croppedImageBlob).then(
                    (imageBitmap) => {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        canvas.width = width;
                        canvas.height = width * imageBitmap.height / imageBitmap.width;

                        ctx.drawImage(imageBitmap, 0, 0, canvas.width, canvas.height);
                        canvas.toBlob(blob => {
                            if (blob.size > 1000000) {
                                this.errors.profile_photo = ['The image is too large, even after resizing.'];
                            } else {
                                delete this.errors.profile_photo;
                                this.storeImage(blob);
                            }
                        }, 'image/jpeg', .5);
                    }
                )
            } else {
                delete this.errors.profile_photo;
                this.storeImage(this.croppedImageBlob)
            }
        },
        storeImage(blob) {
            this.saving = true;
            const formData = new FormData();

            formData.append('profile_photo', blob);

            api.post(`/api/people/${this.person.uuid}/profile-photo`, formData)
                .then(() => {
                    this.$store.dispatch('people/getPerson', { uuid: this.person.uuid });
                    this.showForm = false;
                    this.file = null;
                    this.saving = false;
                })
                .catch(error => {
                    this.saving = false;
                    if (isValidationError(error)) {
                        this.errors = error.response.data.errors;
                        return;
                    } else if (error.response.status === 413) {
                        this.errors.profile_photo = ['The image is too large.'];
                        return;
                    }
                    throw (error);
                })
        },
        cancelCropped() {
            this.showForm = false;
            this.file = null;
        }
    }
}
</script>
<template>
    <div :class="{ 'cursor-wait': saving }">
        <div class="border border-gray-200 rounded-lg">
            <img :src="srcPath" :alt="altText" class="rounded-t-lg w-full" @click="initUpload">
            <button @click="initUpload" class="border border-t-0 bg-gray-200 block w-full">
                Edit
            </button>
        </div>

        <teleport to='body'>
            <modal-dialog v-model="showForm" title="Upload a new profile photo">
                <ImageCropper :image-src="srcPath" @cropped="setCroppedImageBlob" />

                <input-row hideLabel :errors="errors.profile_photo">
                    <input type="file" @change="setFile" ref="fileInput">
                </input-row>

                <button-row submit-text="Save" @submitted="saveCropped" @canceled="cancelCropped" v-if="!saving" />
                <div v-else>Saving...</div>
            </modal-dialog>
        </teleport>
    </div>
</template>

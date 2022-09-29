<template>
    <div :class="{'cursor-wait': saving}">
        <div class="border border-gray-200 rounded-lg">
            <img
                :src="srcPath"
                :alt="altText"
                class="rounded-t-lg w-full"
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
                    @cropped="setCroppedImageBlob"
                />

                <input-row hideLabel :errors="errors.profile_photo">
                    <input type="file" @change="setFile" ref="fileInput">
                </input-row>

                <button-row submit-text="Save" @submitted="saveCropped" @canceled="cancelCropped" v-if="!saving"/>
                <div v-else>Saving...</div>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import ImageCropper from '@/components/ImageCropper.vue'
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
            croppedImageBlob: null,
            errors: {},
            saving: false
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
                return URL.createObjectURL(this.file);
            }
            if (this.person.profile_photo) {
                return `/profile-photos/${this.person.profile_photo}`
            }
            return '/images/default_profile.jpg';
        },
        profilePhoto () {
            if (this.person.profile_photo) {
                return `/profile-photos/${this.person.profile_photo}`
            }
            return '/images/default_profile.jpg';
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
        setCroppedImageBlob (blob) {
            this.croppedImageBlob = blob;
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
        saveCropped () {
            if (this.croppedImageBlob.size > 2000000) {
                const width = 400;
                const img = new Image();
                img.src = URL.createObjectURL(this.croppedImageBlob);

                img.addEventListener('load', () => {
                    var canvas = document.createElement('canvas'),
                    ctx = canvas.getContext("2d"),

                    oc = document.createElement('canvas'),
                    octx = oc.getContext('2d');

                    canvas.width = width; // destination canvas size
                    canvas.height = canvas.width * img.height / img.width;

                    var cur = {
                        width: Math.floor(img.width * 0.5),
                        height: Math.floor(img.height * 0.5)
                    }

                    oc.width = cur.width;
                    oc.height = cur.height;

                    octx.drawImage(img, 0, 0, cur.width, cur.height);

                    while (cur.width * 0.5 > width) {
                        cur = {
                            width: Math.floor(cur.width * 0.5),
                            height: Math.floor(cur.height * 0.5)
                        };
                        octx.drawImage(oc, 0, 0, cur.width * 2, cur.height * 2, 0, 0, cur.width, cur.height);
                    }
                    ctx.drawImage(oc, 0, 0, cur.width, cur.height, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(blob => {
                        console.log(blob.size/1000/1000);
                        this.storeImage(blob);
                    }, null, .5)
                });

                return;
            }

            this.storeImage(this.croppedImageBlob)
        },
        storeImage (blob) {
            this.saving = true;
            const formData = new FormData();

            formData.append('profile_photo', blob);

            api.post(`/api/people/${this.person.uuid}/profile-photo`, formData)
                .then(() => {
                    this.$store.dispatch('people/getPerson', {uuid: this.person.uuid});
                    this.showForm = false;
                    this.file = null;
                    this.saving = false;
                })
                .catch(error => {
                    if (isValidationError(error)) {
                        this.errors = error.response.data.errors;
                        this.saving = false;
                        return;
                    }
                    this.saving = false;
                    throw (error);
                })
        },
        cancelCropped () {
            this.showForm = false;
            this.file = null;
        }
    }
}
</script>

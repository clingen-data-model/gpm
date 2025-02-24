<script setup>
import Cropper from 'cropperjs';
import { debounce } from 'lodash-es'
import { onMounted, onUnmounted, ref, watch } from 'vue';
import 'cropperjs/dist/cropper.css'

const props = defineProps({
    imageSrc: String
})
const emit = defineEmits(['cropped']);

const imageEl = ref(null);
const previewUrl = ref(null);
const cropperInst = ref(null);

const updateCropped = debounce(() => {
    if (cropperInst.value && cropperInst.value.getCroppedCanvas()) {
        cropperInst.value.getCroppedCanvas().toBlob(blob => {
            if (blob) {
                previewUrl.value = URL.createObjectURL(blob)
                emit('cropped', blob);
            }
        }, 'image/jpeg', 0.8);
    }
}, 500)

const sizeCropper = () => {
    const imageData = cropperInst.value.getImageData();
    cropperInst.value.setCropBoxData({
        width: imageData.width,
        height: imageData.height,
    });
}

const setupCropper = () => {
    cropperInst.value = new Cropper(imageEl.value, {
        viewMode: 1,
        dragMode: 'move',
        aspectRatio: 1 / 1,
        backround: false,
        zoomOnWheel: false,
        ready() {
            sizeCropper();
        },
        crop() {
            updateCropped();
        }
    })
}

const zoomIn = () => {
    cropperInst.value.zoom(0.1);
}

const zoomOut = () => {
    cropperInst.value.zoom(-0.1);
}

watch(() => props.imageSrc, (to) => {
    cropperInst.value.replace(to);
    sizeCropper();
});

onMounted(() => {
    setupCropper()
});

onUnmounted(() => {
    cropperInst.value.destroy();
    URL.revokeObjectURL(previewUrl.value);
})
</script>
<template>
    <div>
        <div class="flex justify-between w-3/4">
            <div>
                <button class="btn btn-xs" @click="zoomOut">
                    <zoom-out-icon height="12" width="12" />
                </button>
                &nbsp;
                <button class="btn btn-xs" @click="zoomIn">
                    <zoom-in-icon height="12" width="12" />
                </button>
            </div>
            <popper arrow hover>
                <template #content>
                    <div class="w-80"></div>
                    <h4>Tips on cropping images:</h4>
                    <ul class="list-disc pl-6 text-xs">
                        <li>Drag within the cropper to move the cropped region.</li>
                        <li>Resize the cropped region by clicking on the edges or corners of the cropper and dragging.
                        </li>
                        <li>Drag the image outside of the cropper to move.</li>
                        <li>
                            Use the
                            <zoom-in-icon height="12" width="12" class="inline-block" />
                            and
                            <zoom-out-icon height="12" width="12" class="inline-block" />
                            to zoom in and out.
                        </li>
                    </ul>

                    <note class="pl-6 mt-3">The cropper must be entirely on the image so you may need to reduce the
                        cropped are or zoom out to move the image.</note>
                </template>
                <icon-question height="14" width="14" />
            </popper>
        </div>
        <div class="flex space-x-4">
            <div class="border w-3/4" style="max-height: 400px">
                <img ref="imageEl" :src="imageSrc" style="max-width: 100%">
            </div>
            <div>
                <h4>Preview</h4>
                <img class="border border-gray-700 overflow-hidden drop-shadow" style="width: 100px; height: 100px"
                    :src="previewUrl">
            </div>
        </div>
    </div>
</template>

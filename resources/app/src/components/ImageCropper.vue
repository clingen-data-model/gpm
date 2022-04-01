<template>
    <div>
        <div class="flex space-x-4">
            <div class="border overflow-hidden w-3/4">
                <img :src="imageSrc" ref="imageEl">
            </div>
            <div>
                <h4>Preview</h4>
                <img 
                    class="border border-gray-700 overflow-hidden drop-shadow" 
                    style="width: 100px; height: 100px" 
                    ref="previewEl"
                    :src="previewUrl"
                >
                <!-- <div>
                    <button class="btn" @click="zoomOut">-</button>
                    <button class="btn" @click="zoomIn">+</button>
                </div> -->
            </div>
        </div>
    </div>
</template>
<script setup>
    import 'cropperjs/dist/cropper.css'
    import {debounce} from 'lodash-es'
    import Cropper from 'cropperjs';
    import {ref, onMounted, watch, defineProps, defineEmits} from 'vue';

    const props = defineProps({
        imageSrc: String
    })
    const emit = defineEmits(['cropped']);

    const imageEl = ref(null);
    const previewUrl = ref(null);
    const cropperInst = ref(null);
    
    const emitCropped = debounce(() => {
        cropperInst.value.getCroppedCanvas().toBlob(blob => {
            emit('cropped', blob);
        });
    }, 200)

    const updatePreview = debounce(() => {
        cropperInst.value.getCroppedCanvas().toBlob(blob => {
            if (blob) {
                previewUrl.value = URL.createObjectURL(blob)
            }
        })
    }, 500)

    const sizeCropper = () => {
        console.log('size the cropper');
        const imageData = cropperInst.value.getImageData();
        if (imageData.width == imageData.height) {
            cropperInst.value.setCropBoxData({
                width: imageData.width,
                height: imageData.height,
            })                    
        }

    }

    const setupCropper = () => {
        cropperInst.value = new Cropper(imageEl.value, {
            viewMode: 1,
            dragMode: 'move',
            aspectRatio: 1 / 1,
            backround: false,
            // zoomable: false,
            ready () {
                console.log('ready');
                // this.cropper.crop()
                sizeCropper();
            },
            crop () {
                updatePreview();
                emitCropped();
            }
        })
    }
    
    watch(() => props.imageSrc, (to) => {
        cropperInst.value.replace(to);
        sizeCropper();
    });

    onMounted( () => {
        setupCropper()
    })
</script>
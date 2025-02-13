<script setup>
    
</script>
<template>
    <div class="my-4 p-4 text-sm flex items-start space-x-4 bg-gray-200 rounded-lg">
        <icon-question height="48" width="48" class="text-blue-700"/> 
        <div>
            <h3 style="line-height: 1">Have Questions?</h3>
            <div style="font-size: 1rem; line-height:2">
                <slot></slot>
            </div>
        </div>
    </div>
</template>
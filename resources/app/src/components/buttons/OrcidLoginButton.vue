<template>
    <div>
        <button @click="startLogin" class="btn btn-xs">
            Login using ORCID
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue'

function startLogin() {
    window.open('auth/orcid/redirect', 'ORCID Login');
}
</script>

<style lang="scss" scoped>

</style>

<template>
    <div class="w-24 border border-gray-300 bg-white">
        <img v-if="person.profile_photo_path" :src="person.profile_photo_path">
        <icon-user v-else height="96" width="96" icon-color="#888" />
    </div>
</template>
<script>
import Person from '@/domain/person'


export default {
    name: 'ProfilePicture',
    props: {
        person: {
            required: true,
            type: Person
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        hasProfilePhoto () {
            return this.person.profile_photo_path !== null;
        }
    },
    methods: {

    }
}
</script>
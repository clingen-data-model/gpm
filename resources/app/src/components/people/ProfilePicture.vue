<template>
    <div class="border border-gray-300 bg-white overflow-hidden">
        <img :src="photoUrl" class="w-full">
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
            return this.person.profile_photo !== null;
        },
        photoUrl () {
            if (this.hasProfilePhoto) {
                return `/profile-photos/${this.person.profile_photo}`
            }
            return '/images/default_profile.jpg';
        }
    },
    methods: {

    }
}
</script>
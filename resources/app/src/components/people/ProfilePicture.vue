<template>
    <div class="w-24 border border-gray-300 bg-white">
        <img v-if="hasProfilePhoto" :src="photoUrl">
        <icon-user v-else icon-color="#888" />
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
            return '';
        }
    },
    methods: {

    }
}
</script>
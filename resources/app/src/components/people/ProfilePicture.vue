<template>
    <div class="w-24 border border-gray-300 bg-white">
        <img v-if="person.profile_photo_path" :src="person.profile_photo_path">
        <user-icon v-else height="96" width="96" icon-color="#888"></user-icon>
    </div>
</template>
<script>
import Person from '@/domain/person'
import UserIcon from '@/components/icons/IconUser'

export default {
    name: 'ProfilePicture',
    components: {
        UserIcon,
    },
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
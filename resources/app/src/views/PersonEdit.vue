<template>
    <div>
        <profile-form :person="person"
            @saved="goBack()"
            @canceled="goBack()"
        ></profile-form>
    </div>
</template>
<script>
import {watch, computed, onMounted} from 'vue'
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
import ProfileForm from '@/components/people/ProfileForm'
import Person from '@/domain/person'

export default {
    name: 'PersonEdit',
    components: {
        ProfileForm
    },
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    setup (props) {
        const store = useStore();
        const router = useRouter();

        const person = computed( () => {
            const person = store.getters['people/currentItem'];
            if (person) {
                return person;
            }
            return new Person();
        });
        onMounted(async () => {
            await store.dispatch('people/getPerson', {uuid: person.value.uuid});
        })
        watch(() => props.uuid, async () => {
            await store.dispatch('people/getPerson', {uuid: person.value.uuid});
        })

        const goBack = () => {
            router.go(-1);
        }

        return {
            person,
            goBack,
        }
    }
}
</script>
<script>
export default {
    name: 'DevTodo',
    props: {
        items: {
            required: true,
            type: Array
        }
    },
    data() {
        return {
            
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>
<template>
    <dev-component>
        <h4>TODO</h4>
        <ul>
            <li v-for="(item, idx) in items" :key="idx" class="list-decimal ml-6">
                {{ item }}
            </li>
        </ul>
    </dev-component>
</template>
<script>
export default {
    name: 'Breadcrumps',
    props: {
        items: {
            type: Array,
            required: true
        }
    },
}
</script>
<template>
    <ul>
        <li v-for="(item, idx) in items" :key="idx" class="inline">
            <router-link :to="item.route" class="note">{{ item.label }}</router-link>
            <span v-if="idx !== items.length-1" class="note"> &gt; </span>
        </li>
    </ul>
</template>
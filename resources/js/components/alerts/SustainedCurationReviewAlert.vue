<script>

export default {
    name: 'SustainedCurationReviewAlert',
    props: {
        group: {
            type: Object,
            required: true
        }
    },
}
</script>
<template>
    <static-alert vartiant="info" class="flex space-x-2 items-center">
        <icon-review width="30" height="30"></icon-review>
        <div>
            <p>Congratulations on your specifications pilot approval!</p>
            <router-link 
                :to="{name: 'SustainedCurationReview', params: {uuid: group.uuid}}"
                class="btn font-bold"
            >
                Review your sustained curation information to finish.
            </router-link>
        </div>
    </static-alert>
</template>
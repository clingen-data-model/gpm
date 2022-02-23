<style lang="postcss">
    .link.active {
        @apply text-gray-600;
    }
</style>
<template>
    <div>
        <ul class="flex pagination-control">
            <li>
                <button class="link" @click="goToPreviousPage" :class="{active: currentPage === 0}">&lt;</button>
            </li>
            <li v-for="(page, idx) in displayPages" :key="idx">
                <button 
                    v-if="page.page !== null"
                    class="link px-2" :class="{active: page.page === currentPage}" 
                    @click="$emit('update:currentPage', page.page)"
                >
                    {{page.label}}
                </button>
                <span v-else>&hellip;</span>
            </li>
            <li>
                <button class="link" @click="goToNextPage" :class="{active: currentPage == pagesCount-1}">&gt;</button>
            </li>
        </ul>
    </div>
</template>
<script>
import {range} from 'lodash';

export default {
    name: 'PaginationLinks',
    props: {
        items: {
            type: Array,
            default: () => []
        },
        currentPage: {
            type: Number,
            required: true
        },
        pageSize: {
            type: Number,
            default: 20
        }
    },
    emits: [
        'update:currentPage'
    ],
    data() {
        return {
            
        }
    },
    computed: {
        pagesCount () {
            console.log(this.items.length);
            return Math.ceil(this.items.length/this.pageSize)
        },
        pages () {
            return range(0, this.pagesCount)
        },
        displayPages () {
            const displayPages = [
            ];
            
            this.pages.forEach(p => {
                if (p == 0 
                    || p === this.pagesCount-1
                    || Math.abs(p-this.currentPage) < 3
                ) {
                    displayPages.push({
                        page: p,
                        label: p+1
                    });
                } else {
                    if (displayPages[displayPages.length-1].page === null ) {
                        return;
                    }
                    displayPages.push({page: null, label: '...'})
                }
            })
            
            return displayPages;
        }
    },
    methods: {
        goToPreviousPage () {
            if (this.currentPage === 0) {
                return;
            }
            this.$emit('update:currentPage', this.currentPage - 1);
        },
        goToNextPage () {
            if (this.currentPage === this.pagesCount-1) {
                return;
            }
            this.$emit('update:currentPage', this.currentPage + 1);
        }
    }
}
</script>
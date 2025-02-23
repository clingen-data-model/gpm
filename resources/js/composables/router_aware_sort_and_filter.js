import { useRouter, useRoute } from 'vue-router'
import {computed} from 'vue'

export default function (defaultSort = null) {
    const router = useRouter()
    const route = useRoute()
    if (!defaultSort){
        // eslint-disable-next-line no-console
        console.log('Warning: defaultSort is deprecated.  Please provide a sort object: {field: "fieldname", desc: boolean}')
        defaultSort = (defaultSort) ? defaultSort : {field: 'name', desc: false}
    }
    
    const sort = computed({
        immediate: true,
        get() {
            if (Object.keys(route.query).includes('sort-field')) {
                return {
                    field: route.query['sort-field'],
                    desc: Boolean(Number.parseInt(route.query['sort-desc']))
                }
            }
            return defaultSort;
        },
        set(sortObj) {
            const newSortQuery = {'sort-field': sortObj.field, 'sort-desc': sortObj.desc ? 1 : 0}
    
            const newQuery = {
                ...route.query, 
                ...newSortQuery
            };
    
            router.replace({path: route.path, query: newQuery})
        }
    });
    
    const filter = computed({
        set(value) {
            let currentQuery = route.query;
            let currentPath = route.path;
    
            let updatedQuery = {...currentQuery};
    
            if (!value) {
                delete updatedQuery.filter;
            } else {
                updatedQuery = {...currentQuery, ...{'filter': value} };
            }
    
            router.replace({path: currentPath, query: updatedQuery})
        },
        get() {
            return route.query.filter
        },
        immediate: true
    });

    return {
        sort,
        filter
    }
}

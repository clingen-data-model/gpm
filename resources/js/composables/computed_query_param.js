import { useRouter, useRoute } from 'vue-router'
import {computed} from 'vue'

export default function (paramName, defaultValue) {
    const router = useRouter()
    const route = useRoute()
    
    const routerComputed = computed({
        immediate: true,
        get() {
            if (Object.keys(route.query).includes(paramName)) {
                return route.query.paramName
            }
            return defaultValue
        },
        set(newValue) {
            const newQuery = {...route.query};
            newQuery.paramName = newValue

            router.replace({path: route.path, query: newQuery})
        }            
    });

    return routerComputed;
}
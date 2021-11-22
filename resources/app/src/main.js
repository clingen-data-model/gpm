import { createApp } from 'vue'
import App from './App.vue'
import './registerServiceWorker'
import router from './router'
import store from './store'

import CKEditor from '@ckeditor/ckeditor5-vue'
import {titleCase, camelCase, snakeCase, kebabCase} from '@/utils'


const app = createApp(App)
const registerComponentsInContext = (context => {
    context.keys().forEach(filePath => {
        const componentName = kebabCase(filePath.split('/').pop().split('.')[0]);
        const comp = context(filePath)
        app.component(componentName, comp.default);
    });    
})
registerComponentsInContext(require.context('@/components', false, /\.vue$/i))        
registerComponentsInContext(require.context('@/components/alerts', false, /\.vue$/i))        
registerComponentsInContext(require.context('@/components/dev', false, /\.vue$/i))        
registerComponentsInContext(require.context('@/components/forms', false, /\.vue$/i))        
registerComponentsInContext(require.context('@/components/icons', false, /\.vue$/i))        
registerComponentsInContext(require.context('@/components/buttons', false, /\.vue$/i))        

import ClickOutside from './directives/click_outside'
app.directive('click-outside', ClickOutside)
import RemainingHeight from '@/directives/remaining_height'
app.directive('remaining-height', RemainingHeight)

import {formatDate} from '@/date_utils'

import {userCan, hasPermission, hasAnyPermission, hasRole, userIsPerson} from '@/auth_utils'

import objectUid from '@/object_uid'
import {register} from 'register-service-worker'

app.use(store)
    .mixin({
        methods: {
            userCan,
            hasPermission,
            hasAnyPermission,
            hasRole,
            userIsPerson,
            formatDate,
            titleCase,
            camelCase,
            snakeCase,
            kebabCase,
        }
    })
    .mixin(objectUid)
    .use(router)
    .use(CKEditor)
    .mount('#app')
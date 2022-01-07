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

import SubmissionWrapper from '@/components/groups/SubmissionWrapper';
app.component('submission-wrapper', SubmissionWrapper);

import ClickOutside from './directives/click_outside'
app.directive('click-outside', ClickOutside)
import RemainingHeight from '@/directives/remaining_height'
app.directive('remaining-height', RemainingHeight)



import Popper from "vue3-popper"
app.component('popper', Popper);

import {formatDate, formatDateTime, formatTime} from '@/date_utils'

import {userCan, hasPermission, hasAnyPermission, hasRole, userIsPerson, userInGroup} from '@/auth_utils'

import objectUid from '@/object_uid'

import "./assets/styles/popper-theme.css"

app.config.globalProperties.append = (path, pathToAppend) =>
  path + (path.endsWith('/') ? '' : '/') + pathToAppend
  
app.use(store)
    .mixin({
        methods: {
            userCan,
            hasPermission,
            hasAnyPermission,
            hasRole,
            userIsPerson,
            formatDate,
            formatDateTime,
            formatTime,
            titleCase,
            camelCase,
            snakeCase,
            kebabCase,
            userInGroup,
        }
    })
    .mixin(objectUid)
    .mixin({
        mounted () {
            if (this.id) {
                if (this.$route.hash) {
                    if (this.$route.hash.substr(1) == this.id) {
                        location.href = '#';
                        location.href = this.$route.hash;
                    }
                }
            }
        }
    })
    .use(router)
    .use(CKEditor)
    .mount('#app')
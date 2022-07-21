import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'

import CKEditor from '@ckeditor/ckeditor5-vue'
import {titleCase, camelCase, snakeCase, kebabCase, sentenceCase} from '@/utils'

// Remove all service workers
console.log('removing service workers');
if(window.navigator && navigator.serviceWorker) {
    navigator.serviceWorker.getRegistrations()
    .then(function(registrations) {
      for(let registration of registrations) {
        registration.unregister();
      }
    });
  }


const app = createApp(App)
const registerComponentsInContext = (context => {
    context.keys().forEach(filePath => {
        if (!filePath.match('.vue')) {
            return;
        }
        const componentName = kebabCase(filePath.split('/').pop().split('.')[0]);
        const comp = context(filePath)
        app.component(componentName, comp.default);
    });    
})
registerComponentsInContext(require.context('@/components', false, /\.vue$/i));
registerComponentsInContext(require.context('@/components/links'), false, /\.vue$/i);
registerComponentsInContext(require.context('@/components/alerts'), false, /\.vue$/i);
registerComponentsInContext(require.context('@/components/dev'), false, /\.vue$/i);
registerComponentsInContext(require.context('@/components/forms'), false, /\.vue$/i);
registerComponentsInContext(require.context('@/components/icons'), false, /\.vue$/i);
registerComponentsInContext(require.context('@/components/buttons'), false, /\.vue$/i);
registerComponentsInContext(require.context('@/components/mail'), false, /\.vue$/i);

import SubmissionWrapper from '@/components/groups/SubmissionWrapper.vue';
app.component('submission-wrapper', SubmissionWrapper);

import ScreenTemplate from '@/components/ScreenTemplate.vue';
app.component('ScreenTemplate', ScreenTemplate);

import ClickOutside from './directives/click_outside'
app.directive('click-outside', ClickOutside)
import RemainingHeight from '@/directives/remaining_height'
app.directive('remaining-height', RemainingHeight)

import PopOver from "@/components/PopOver.vue"
app.component('popper', PopOver);
app.component('popover', PopOver);
import GroupBreadcrumbs from '@/components/groups/GroupBreadcrumbs.vue'
app.component('group-breadcrumbs', GroupBreadcrumbs);


import {formatDate, formatDateTime, formatTime, addDays, yearAgo} from '@/date_utils'

import {userCan, hasPermission, hasAnyPermission, hasRole, userIsPerson, userInGroup, coordinatesPerson} from '@/auth_utils'

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
            coordinatesPerson,
            formatDate,
            formatDateTime,
            formatTime,
            addDays,
            yearAgo,
            titleCase,
            camelCase,
            snakeCase,
            kebabCase,
            userInGroup,
            sentenceCase
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
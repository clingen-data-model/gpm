import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'

import CKEditor from '@ckeditor/ckeditor5-vue'
import {titleCase, camelCase, snakeCase, kebabCase, sentenceCase} from '@/string_utils'

const app = createApp(App)

const registerComponents = (modules) => {
    for (const path in modules) {
        const componentName = kebabCase(path.split('/').pop().split('.')[0]);
        app.component(componentName, modules[path].default);
    }
}

registerComponents(import.meta.glob('@/components/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/links/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/alerts/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/dev/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/forms/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/icons/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/buttons/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/mail/*.vue', { eager: true }))

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

/* eslint import/first: 0 */

import {camelCase, kebabCase, sentenceCase, snakeCase, titleCase} from '@/string_utils'
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

import store from './store'

const app = createApp(App)

const registerComponents = (modules) => {
    for (const path in modules) {
        const componentName = kebabCase(path.split('/').pop().split('.')[0]);
        app.component(componentName, modules[path].default);
    }
}

/*
registerComponents(import.meta.glob('@/components/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/links/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/alerts/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/dev/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/buttons/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/mail/*.vue', { eager: true }))
*/

// Really common components
registerComponents(import.meta.glob('@/components/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/forms/*.vue', { eager: true }))
registerComponents(import.meta.glob('@/components/icons/*.vue', { eager: true }))

import StaticAlert from './components/alerts/StaticAlert.vue'
app.component('static-alert', StaticAlert)

import SubmissionWrapper from '@/components/groups/SubmissionWrapper.vue';
app.component('submission-wrapper', SubmissionWrapper);

import ScreenTemplate from '@/components/ScreenTemplate.vue';
app.component('ScreenTemplate', ScreenTemplate);

import FaqLink from '@/components/links/FaqLink.vue'
app.component('faq-link', FaqLink)

import ClickOutside from './directives/click_outside'
app.directive('click-outside', ClickOutside)
import RemainingHeight from '@/directives/remaining_height'
app.directive('remaining-height', RemainingHeight)

import PopOver from "@/components/PopOver.vue"
app.component('popper', PopOver);
app.component('popover', PopOver);
import GroupBreadcrumbs from '@/components/groups/GroupBreadcrumbs.vue'
app.component('group-breadcrumbs', GroupBreadcrumbs);


import {coordinatesPerson, hasAnyPermission, hasPermission, hasRole, userCan, userInGroup, userIsPerson} from '@/auth_utils'

import {addDays, formatDate, formatDateTime, formatTime, yearAgo} from '@/date_utils'

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
                    if (this.$route.hash.substr(1) === this.id) {
                        location.href = '#';
                        location.href = this.$route.hash;
                    }
                }
            }
        }
    })
    .use(router)
    .mount('#app')

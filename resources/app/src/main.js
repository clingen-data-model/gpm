import { createApp } from 'vue'
import App from './App.vue'
import './registerServiceWorker'
import router from './router'
import store from './store'

import CKEditor from '@ckeditor/ckeditor5-vue'

const app = createApp(App)


import StaticAlert from './components/alerts/StaticAlert'
app.component('static-alert', StaticAlert);

import IconBase from './components/icons/IconBase'
app.component('icon-base', IconBase);

import Note from './components/Note'
app.component('note', Note);

import DropdownMenu from './components/DropdownMenu'
app.component('dropdown-menu', DropdownMenu);

import TruncateExpander from './components/TruncateExpander'
app.component('truncate-expander', TruncateExpander);

import Card from './components/Card';
app.component('card', Card);

import DataTable from './components/DataTable'
app.component('data-table', DataTable);

import ModalDialog from './components/ModalDialog'
app.component('modal-dialog', ModalDialog);

import Collapsible from './components/Collapsible'
app.component('collapsible', Collapsible);

import DevComponent from './components/dev/DevComponent'
app.component('dev-component', DevComponent);
import DevTodo from './components/dev/DevTodo'
app.component('dev-todo', DevTodo);

import FormContainer from './components/forms/FormContainer'
app.component('form-container', FormContainer);
import Checkbox from '@/components/forms/Checkbox';
app.component('checkbox', Checkbox);
import DateInput from './components/forms/DateInput'
app.component('date-input', DateInput);
import InputRow from './components/forms/InputRow'
app.component('input-row', InputRow);
import ButtonRow from './components/forms/ButtonRow'
app.component('button-row', ButtonRow);

import TabsContainer from './components/TabsContainer'
import TabItem from './components/TabItem'
app.component('tabs-container', TabsContainer);
app.component('tab-item', TabItem);

import DictionaryRow from './components/DictionaryRow'
app.component('dictionary-row', DictionaryRow)
app.component('dict-row', DictionaryRow)

import ObjectDictionary from './components/ObjectDictionary'
app.component('object-dictionary', ObjectDictionary)
app.component('object-dict', ObjectDictionary)

import ClickOutside from './directives/click_outside'
app.directive('click-outside', ClickOutside)

const d = app

app.use(store)
    .mixin({
        methods: {
            userCan: permission => store.state.user.hasPermission(permission),
            hasPermission: permission => store.state.user.hasPermission(permission),
            hasRole: role => store.state.user.hasRole(role)
        }
    })
    .use(router)
    .use(CKEditor)
    .mount('#app')
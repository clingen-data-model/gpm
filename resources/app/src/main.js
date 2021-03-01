import { createApp } from 'vue'
import App from './App.vue'
import './registerServiceWorker'
import router from './router'
import store from './store'

const app = createApp(App)
import IconBase from './components/icons/IconBase'
app.component('icon-base', IconBase);

import Card from './components/Card';
app.component('card', Card);

import DataTable from './components/DataTable'
app.component('data-table', DataTable);

import ModalDialog from './components/ModalDialog'
app.component('modal-dialog', ModalDialog);

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

app.use(store).use(router).mount('#app')
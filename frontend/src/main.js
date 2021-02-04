import { createApp } from 'vue'
import App from './App.vue'
import './registerServiceWorker'
import router from './router'
import store from './store'

const app = createApp(App)
import DataTable from './components/DataTable'
app.component('data-table', DataTable);

import ModalDialog from './components/ModalDialog'
app.component('modal-dialog', ModalDialog);

import InputRow from './components/forms/InputRow'
app.component('input-row', InputRow);

app.use(store).use(router).mount('#app')
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import './assets/custom-bootstrap.css'
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'

const app = createApp(App)
app.use(router)
app.use(createPinia())
app.use(Toast, {
    position: "top-right",
    timeout: 3000,
    closeOnClick: true
});
app.mount('#app')

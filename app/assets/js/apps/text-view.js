import { createApp } from 'vue'
import TextViewApp from '../components/Text/TextViewApp.vue'

import {i18n} from "../locales/i18n";

const app = createApp({})
app.use(i18n)
app.component('text-view-app', TextViewApp)
app.mount('#app')

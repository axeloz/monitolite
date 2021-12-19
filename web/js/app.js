//window.Vue = require('vue')

import Vue from 'vue'
import App from '../resources/app.vue'

import axios from 'axios'
Vue.prototype.$http = axios

import moment from 'moment'
Vue.prototype.moment = moment

var runApp = function() {
	const app = new Vue(App).$mount('#app');
}

window.addEventListener('load', function () {
	runApp();
})
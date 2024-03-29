//window.Vue = require('vue')

import Vue from 'vue'

import Vuex from 'vuex'
Vue.use(Vuex)

import VueRouter from 'vue-router'
Vue.use(VueRouter)

import axios from 'axios'
Vue.prototype.$http = axios

import moment from 'moment'
Vue.prototype.moment = moment

import VueApexCharts from 'vue-apexcharts'
Vue.use(VueApexCharts)
Vue.component('apexchart', VueApexCharts)

import VueLoading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';
Vue.use(VueLoading, {
    // Optional parameters
    //container: this.fullPage ? null : this.$refs.formContainer,
    canCancel: true,
    backgroundColor: '#000',
    color: '#0a9f9a',
    width: 128,
    height: 128,
    opacity: 0.9,
    loader: 'dots'
})

import Home from '../views/app.vue'
import TaskDetails from '../views/taskdetails.vue'
const router = new VueRouter({
    mode: 'history',
    routes: [
        {
            path: '/',
            name: 'home',
            component: Home
        },
        {
            path: '/task/:id',
            name: 'taskdetails',
            component: TaskDetails,
        },
    ],
});

const store = new Vuex.Store({
    state: {
      tasks: null
    },
    mutations: {
        setTasks(state, tasks) {
            state.tasks = tasks
        },
        updateTask(state, update) {
            let tasks = state.tasks

            if (
                tasks.hasOwnProperty(update.group_id) &&
                tasks[update.group_id].hasOwnProperty('tasks') &&
                tasks[update.group_id]['tasks'].hasOwnProperty(update.id)
            ) {
                tasks[update.group_id]['tasks'][update.id] = update;
            }
        }
    }
})

var runApp = function() {

    new Vue({
        router,
        components: { Home },
        store,
      }).$mount('#app')
}

window.addEventListener('load', function () {
	runApp();
})
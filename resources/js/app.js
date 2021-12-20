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
        }
    },
    actions: {
        updateTask(state, update) {
            //let tasks = state.tasks

            for (let i in state.tasks[update.group_id]['tasks']) {
                if (state.tasks[update.group_id]['tasks'][i].id == update.id) {
                    //tasks[update.group_id]['tasks'][i] = update
                    state.tasks[update.group_id]['tasks'][i] = Object.assign({}, state.tasks[update.group_id]['tasks'][i], update)
                }
            }
            console.log(state.tasks)
        }
    }
})

var runApp = function() {

    new Vue({
        router,
        store,
        render: h => h(Home)
      }).$mount('#app')

    // const app = new Vue({
    //     el: '#app',
	// 	components: { Home },
	// 	router,
	// });

}

window.addEventListener('load', function () {
	runApp();
})
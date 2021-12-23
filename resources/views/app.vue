<template>
    <div class="container">
        <h1>MonitoLite Dashboard</h1>
        <p class="refreshed-time">Last refresh: <br /><span class="clock">{{ refreshedTime }}</span></p>
        <quick-view></quick-view>
        <task-list></task-list>
    </div>
</template>

<script>

    import TaskList from './components/tasklist.vue'
    import QuickView from './components/quickview.vue'
    export default{
        components: {
            QuickView,
            TaskList,
        },
        data: function() {
            return {
                refreshed_time: null,
                refresh: null,
                loading: true,
                color: '#FF0000',
                size: '10rem',
            }
        },
        computed: {
            refreshedTime: function() {
                return this.refreshed_time != null ? this.moment(this.refreshed_time).format('HH:mm:ss') : 'never'
            }
        },
        methods: {
            getTasks: function() {
                this.$http.get('/api/getTasks')
                .then(response => this.$store.commit('setTasks', response.data))
                .then(() => {
                    this.refreshed_time = this.moment();
                    this.loading.hide()
                })
                .catch(error => window.alert('Cannot get tasks'))
                this.refreshed_time = this.moment();
            }
        },
        beforeRouteLeave(to, from, next) {
            clearTimeout(this.refresh);
            next();
        },
        mounted: function() {
            this.loading = this.$loading.show()
            this.getTasks()
            this.refresh = window.setInterval(() => {
                this.getTasks();
            }, 10000)
        }
    }
</script>

<style scoped>

</style>
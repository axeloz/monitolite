<template>
    <div class="container">
        <h1>MonitoLite Dashboard</h1>
        <p class="refreshed-time">Data refreshed: {{ refreshedTime }}</p>
        <quick-view :tasks="tasks"></quick-view>
        <group-list :tasks="tasks"></group-list>
    </div>
</template>

<script>

    import QuickView from '../resources/quickview.vue'
    import GroupList from './grouplist.vue'

    export default{
        components: {
            QuickView,
            GroupList
        },
        props: [
            'refresh'
        ],
        data: {
            tasks: [],
            refreshed_time: null
        },
        computed: {
            refreshedTime: function() {
                return this.refreshed_time != null ? this.moment(this.refreshed_time).format('H:mm:ss') : 'never'
            }
        },
        methods: {
            getTasks: function() {
                this.$http.get('api.php?a=get_tasks')
                .then(response => this.tasks = response.data)
                .then(() => {
                    this.refreshed_time = this.moment();
                })
                .catch(error => window.alert('Cannot get tasks'))
                this.refreshed_time = this.moment();
            }
        },
        mounted: function() {
            this.getTasks()
            this.refresh = window.setInterval(() => {
                this.getTasks();
            }, 60000)
        }
    }
</script>

<style scoped>
.refreshed-time {
    text-align: right;
    font-size: .8rem;
}
</style>
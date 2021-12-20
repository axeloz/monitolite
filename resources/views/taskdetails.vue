<template>
	<div class="container">
		<h3>
			Task {{ task.id }}
			<!-- <p class="context-menu"><img src="/img/menu.svg" width="40" /></p> -->
		</h3>
	</div>
</template>

<script>

    export default{
		data: function() {
			return {
				task: null
			}
		},
		mounted: function() {
			let task_id = this.$route.params.id ?? null
			console.log(task_id)

			if (task_id != null) {
				this.$http.get('/api/getTask/'+task_id)
				.then(response => this.task = response.data)
			}
		}
    }
</script>
<template>
	<div>
		<h3>
			Task
			<!-- <p class="context-menu"><img src="/img/menu.svg" width="40" /></p> -->
		</h3>
	</div>
</template>

<script>

    export default{
		mounted: function() {
			let task_id = this.$route.params.id ?? null
			console.log(task_id)

			if (task_id != null) {
				this.$http.get('api.php?a=get_task&id='+task_id)
			}
		}
    }
</script>

<style scoped>
.refreshed-time {
    text-align: right;
    font-size: .8rem;
}
</style>
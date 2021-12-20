<template>
	<div>
		<form
			v-on:submit.prevent="addTask"
		>
			<button>Add task</button>
		</form>
	</div>
</template>

<script>

export default {
	props: [
		'tasks'
	],
	methods: {
		addTask: function() {
			this.$http.post('api.php?a=add_task')
		}
	}
}
</script>


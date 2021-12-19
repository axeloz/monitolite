<template>
	<div class="tasks">
		<div
			v-for="group in tasks"
			v-bind:key="group.id"
			class="task"
		>
			<a :name="'group-'+group.id"></a>
			<h3>
				Tasks for group <span class="highlight">{{ group.name }} <small>(#{{ group.id }})</small></span>
				<p class="context-menu"><img src="img/menu.svg" width="40" /></p>
			</h3>

			<div class="block-content">
				<task-list :tasks="group.tasks"></task-list>
			</div>
		</div>
	</div>
</template>

<script>

import TaskList from './tasklist.vue'

export default {
	components: {
		TaskList
	},
	props: [
		'tasks'
	],
	methods: {
		statusText: function (status) {
			switch (status) {
				case '1':
					return 'up';
				break;
				case '0':
					return 'down';
				break;
				default:
					return 'unknown';
			}
		},
	}
}
</script>


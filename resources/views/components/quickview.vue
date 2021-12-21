<template>
	<div class="quick-view">
		<h3>
			Quick overview
		</h3>
		<div class="block-content">
			<div
				v-if="tasks.length > 0"
			>
				<div
					v-for="group in tasks"
					v-bind:key="group.id"
					class="new-group"
					:title="'Group: '+group.name"
				>
					<a :href="'#group-'+group.id">
						<p
							v-for="task in group.tasks"
							v-bind:key="task.id"
							:href="'#task-'+task.id"
							:class="statusText(task.status)+(task.active == 0 ? ' inactive' : '')"
							class="square"
						>
							<span class="small">{{task.id }}</span>
						</p>
					</a>
				</div>
				<p class="spacer">&nbsp;</p>
			</div>
			<div
				v-else
			>
				<center>Sorry, there is no task here.</center>

			</div>
		</div>
	</div>
</template>

<script>

export default {
	computed: {
		tasks: function() {
			return this.$store.state.tasks
		}
	},
	methods: {
		statusText: function (status) {
			switch (status) {
				case 1:
					return 'up';
				break;
				case 0:
					return 'down';
				break;
				default:
					return 'unknown';
			}
		},
	}
}
</script>


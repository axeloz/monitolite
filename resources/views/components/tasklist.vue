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
				<!-- <p class="context-menu"><img src="img/menu.svg" width="40" /></p> -->
			</h3>

			<div class="block-content">
				<table id="tasks_tbl">
					<thead>
						<tr>
							<th width="5%">Up?</th>
							<th width="*">Host</th>
							<th width="5%">Type</th>
							<th width="20%">Last checked</th>
							<th width="13%">Frequency (min)</th>
							<th width="5%">Active</th>
							<th width="5%">Actions</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="task in group.tasks"
							v-bind:key="task.id"
						>
							<td :class="statusText(task.status)">
								<img :src="'img/'+statusText(task.status)+'.svg'" width="16" alt="Status" />
							</td>
							<td>
								<img src="/img/external.svg" alt="View host" width="16">
								<a :href="task.host" target="_blank">{{ task.host }}</a>
							</td>
							<td>
								<img :src="task.type == 'http' ? 'img/http.svg' : 'img/ping.svg'" width="16" alt="Type of check" :title="'Type: '+task.type" />
							</td>
							<td>
								<span
									v-if="task.last_execution"
								>
									{{ moment(task.last_execution).fromNow() }}
									<img src="img/info.svg" alt="Infos" width="16" :title="'Result: '+task.output" />
								</span>
								<span
									v-else
								>
									Never
								</span>
							<td>{{ task.frequency / 60 }}</td>
							<td>
								<a
									v-on:click.prevent="disableTask(task.id, task.active)"
									href="#"
									:title="task.active == 1 ? 'Disable task' : 'Enable task'"
								>
									<img :src="task.active == 1 ? '/img/on.svg' : '/img/off.svg'" alt="Disable" width="24" />
								</a>
							</td>
							<td>
								<router-link :to="{ name: 'taskdetails', params: { id: task.id }}">
									<img src="img/see.svg" alt="Details" width="20" />
								</router-link>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</template>

<script>

export default {
	components: {
	},
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
		disableTask: function(task_id, current_status) {
			this.$http.post('/api/toggleTaskStatus/'+task_id, {
				active: + !current_status
			})
			.then(response => this.$store.commit('updateTask', response.data))
		}
	}
}
</script>


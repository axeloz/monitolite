<template>
	<div>
		<div class="container"
			v-if="task"
		>
			<h1>
				Task #{{ task.id }}
				<!-- <p class="context-menu"><img src="/img/menu.svg" width="40" /></p> -->
			</h1>


			<h3>History log</h3>
			<p>Showing the latest {{ task.limit }} history records</p>

			<table id="tasks_tbl">
					<thead>
						<tr>
							<th width="20%">Date</th>
							<th width="*">Output</th>
							<th width="10%">Status</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="history in task.history"
							v-bind:key="history.id"
							:class="task.active == 0 ? 'inactive' : ''"
						>
							<td>{{ moment(history.created_at).format('YYYY-MM-DD HH:mm:ss') }}</td>
							<td>
								<span v-if="history.output">
									{{ history.output }}
								</span>
								<span v-else>
									<i>No output</i>
								</span>
							</td>
							<td :class="statusText(task.status)">
								<img :src="'/img/'+statusText(history.status)+'.svg'" width="16" alt="Status" />
							</td>
						</tr>
					</tbody>
				</table>
		</div>
	</div>
</template>

<script>

    export default{
		data: function() {
			return {
				task: null
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
			}
		},
		mounted: function() {
			let task_id = this.$route.params.id ?? null

			if (task_id != null) {
				this.$http.get('/api/getTask/'+task_id)
				.then(response => this.task = response.data)
			}
		}
    }
</script>
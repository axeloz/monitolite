<template>
	<div>
		<table id="tasks_tbl">
			<thead>
				<tr>
					<th width="5%">Up?</th>
					<th width="*">Host</th>
					<th width="5%">Type</th>
					<th width="20%">Last checked</th>
					<th width="13%">Frequency (min)</th>
					<th width="5%">Active</th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="task in tasks"
					v-bind:key="task.id"
				>
					<td :class="statusText(task.status)">
						<img :src="'img/'+statusText(task.status)+'.png'" width="16" alt="Status" />
					</td>
					<td>
						<a :href="task.host" target="_blank">{{ task.host }}</a>
					</td>
					<td>
						<img :src="task.type == 'http' ? 'img/http.png' : 'img/ping.png'" width="16" alt="Type of check" :title="'Type: '+task.type" />
					</td>
					<td>
						<span
							v-if="task.last_execution"
						>
							{{ moment(task.last_execution).fromNow() }}
							<img src="img/info.png" alt="Infos" width="16" :title="'Result: '+task.output" />
						</span>
						<span
							v-else
						>
							Never
						</span>
					<td>{{ task.frequency }}</td>
					<td>{{ task.active == 1 ? 'Yes' : 'No' }}</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>

export default {
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


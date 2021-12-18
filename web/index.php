<?php require_once __DIR__.'/DB.php'; ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<title>MonitoLite - Network monitoring tool</title>
		<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
		<script type="text/javascript" src="js/scripts.js"></script>
		<link type="text/css" rel="stylesheet" href="css/styles.css" />
	</head>

	<body>
		<div id="app">
			<h1>MonitoLite Dashboard</h1>

			<div class="quick-view">
				<h3>Quick overview</h3>
				<div
					v-for="group in tasks"
					v-bind:key="group.id"
					class="new-group"
					:title="group.name"
				>
					<a
						v-for="task in group.tasks"
						v-bind:key="task.id"
						:href="'#task-'+task.id"
					>
						<p :class="statusText(task.status)" class="square">
							<img :src="'/img/'+statusText(task.status)+'.png'" width="16" alt="">
						</p>
					</a>
				</div>
				<p class="spacer">&nbsp;</p>
			</div>


			<div
				v-for="group in tasks"
				v-bind:key="group.group_id"
				class="task"
			>
				<h3>Tasks for group <span class="highlight">{{ group.name }}</span> <small>(#{{ group.id }})</small> </h3>
				<table id="tasks_tbl">
					<thead>
						<tr>
							<th width="5%">Up?</th>
							<th width="*">Host</th>
							<th width="5%">Type</th>
							<th width="20%">Last execution</th>
							<th width="20%">Frequency (min)</th>
							<th width="5%">Active</th$query>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="task in group.tasks"
							v-bind:key="task.id"
						>
							<td :class="statusText(task.status)">
								<img :src="'img/'+statusText(task.status)+'.png'" width="16" alt="Status" />
								<a :name="'task-'+task.id"></a>
							</td>
							<td :class="statusText(task.status)">
								<a :href="task.host" target="_blank">{{ task.host }}</a>
							</td>
							<td>
								<img :src="task.type == 'http' ? 'img/http.png' : 'img/ping.png'" width="16" alt="Type of check" :title="'Type: '+task.type" />
							</td>
							<td>{{ task.last_execution ?? 'never' }}</td>
							<td>{{ task.frequency }}</td>
							<td>{{ task.active == 1 ? 'Yes' : 'No' }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<script>
			var vm = new Vue({
				el: '#app',
				props: [
					'refresh'
				],
				data: {
					tasks: []
				},
				methods: {
					// a computed getter
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
					getTasks: function() {
						axios.get('api.php?a=get_tasks')
						.then(response => this.tasks = response.data)
						.catch(error => window.alert('Cannot get tasks'))
					}
				},
				mounted: function() {
					this.getTasks()
					this.refresh = window.setInterval(() => {
						this.getTasks();
					}, 60000)
				}
			})
		</script>
	</body>
</html>

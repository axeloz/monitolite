<template>
	<div>
		<div class="container"
			v-if="task"
		>
			<h1>
				Task #{{ task.id }}
				<!-- <p class="context-menu"><img src="/img/menu.svg" width="40" /></p> -->
			</h1>

			Show:
			<select
				v-model="chart.days"
				@change="refreshGraph"
			>
				<option value="7">7 days</option>
				<option value="15">15 days</option>
				<option value="30">30 days</option>
			</select>
			<h3>Uptime: past {{ chart.days }} days</h3>
			<div id="chart">
				<apexchart v-if="chart.render" type="bar" height="350" :options="chartOptions" :series="series"></apexchart>
			</div>

			<h3>Last {{ chart.days }} days history log</h3>
			<div v-if="task.history.length > 0">
				<p><i>Showing only records where status has changed</i></p>
				<table id="tasks_tbl">
					<thead>
						<tr>
							<th width="20%">Date</th>
							<th width="20%">Time</th>
							<th width="*">Output</th>
							<th width="10%">Status</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="history in task.history"
							v-bind:key="history.id"
						>
							<td>{{ moment(history.date).format('YYYY-MM-DD') }}</td>
							<td>{{ moment(history.created_at).format('HH:mm:ss') }}</td>
							<td>
								<span v-if="history.output">
									{{ history.output }}
								</span>
								<span v-else>
									<i>No output</i>
								</span>
							</td>
							<td :class="statusText(history.status)">
								<img :src="'/img/'+statusText(history.status)+'.svg'" width="16" alt="Status" />
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<p v-else>No history to display here</p>
		</div>
	</div>
</template>

<script>

    export default{
		data: function() {
			return {
				task: null,

				chart: {
					render: false,
					days: 15
				},

				series: [{
					data: []
				}],
				noData: {
					text: 'Loading...'
				},
				chartOptions: {
					responsive: [{
						breakpoint: 480,
						options: {
							legend: {
								position: 'bottom',
								offsetX: -10,
								offsetY: 0
							}
						}
					}],
					xaxis: {
						categories: [],
					},
					fill: {
						opacity: .9
					},
					legend: {
						position: 'right',
						offsetX: 0,
						offsetY: 50
					},
				},
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
			refreshGraph: function() {
				this.$http.post('/api/getTaskGraph/'+this.task.id, {
					days: this.chart.days
				})
				.then(response => {
					let xaxis = [];
					let new_data_a = [];
					let new_data_b = [];
					console.log(response.data)

					for (let date in response.data) {
						xaxis.push(date)
						new_data_a.push(response.data[date]['up'])
						new_data_b.push(response.data[date]['down'])
					}

					this.chartOptions = {
						xaxis: {
							categories: xaxis,
							labels: {
								show: true,
								rotate: -45,
								rotateAlways: true,
							}
						},
						chart: {
							type: 'bar',
							height: 300,
							stacked: true,
							stackType: '100%'
						},
					}
					this.series = [{
						name: 'UP',
						data: new_data_a,
						color: '#00955c'
					},
					{
						name: 'DOWN',
						data: new_data_b,
						color: '#ef3232'
					}]

					this.chart.render = true
				})
			}
		},
		mounted: function() {
			let task_id = this.$route.params.id ?? null

			if (task_id != null) {
				this.$http.post('/api/getTask/'+task_id, {
					days: this.chart.days
				})
				.then(response => this.task = response.data)
				.then(() => {
					this.refreshGraph()
				})
			}
		}
    }
</script>

<style scoped>
#chart {
	margin-top: 3rem;
}
</style>
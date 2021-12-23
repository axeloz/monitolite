<template>
	<div>
		<div class="container"
			v-if="task.id != null"
		>
			<h1>
				<span class="highlight">{{ task.type }}</span> for host <span class="highlight">{{ task.host }}</span>
				<!-- <p class="context-menu"><img src="/img/menu.svg" width="40" /></p> -->
			</h1>

			Show:
			<select
				v-model="chart.days"
				@change="refreshTask"
			>
				<option value="7">7 days</option>
				<option value="15">15 days</option>
				<option value="30">30 days</option>
			</select>

			<!-- Chart block -->
			<div id="chart" class="round">
				<h3>Uptime: past {{ chart.days }} days</h3>
				<div class="block-content">
					<apexchart class="graph" v-if="chart.render" type="bar" height="350" :options="chartOptions" :series="series"></apexchart>
				</div>
			</div>

			<!-- History backlog -->
			<div class="round">
				<h3>Last {{ chart.days }} days history log</h3>
				<div class="block-content" v-if="history">
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
								v-for="h in history"
								v-bind:key="h.id"
							>
								<td>{{ moment(h.created_at).format('YYYY-MM-DD') }}</td>
								<td>{{ moment(h.created_at).format('HH:mm:ss') }}</td>
								<td>
									<span v-if="h.output">
										{{ h.output }}
									</span>
									<span v-else>
										<i>No output</i>
									</span>
								</td>
								<td :class="statusText(h.status)">
									<img :src="'/img/'+statusText(h.status)+'.svg'" width="16" alt="Status" />
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<p v-else><center>No history to display here</center></p>
			</div>

			<!-- Notifications block -->
			<div class="round">
				<h3>Last {{ chart.days }} days notifications log</h3>
				<div class="block-content" v-if="notifications">
					<table id="tasks_tbl">
						<thead>
							<tr>
								<th width="20%">Date</th>
								<th width="20%">Time</th>
								<th width="*">Firstname</th>
								<th width="10%">Lastname</th>
								<th width="10%">Email</th>
								<th width="10%">Status</th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="n in notifications"
								v-bind:key="n.id"
							>
								<td>{{ moment(n.created_at).format('YYYY-MM-DD') }}</td>
								<td>{{ moment(n.created_at).format('HH:mm:ss') }}</td>
								<td>{{ n.contact.firstname }}</td>
								<td>{{ n.contact.surname }}</td>
								<td>{{ n.contact.email }}</td>
								<td>{{ n.status }}</td>
							</tr>
						</tbody>
					</table>
				</div>
				<p v-else><center>No notification to display here</center></p>
			</div>
		</div>
	</div>
</template>

<script>

    export default{
		data: function() {
			return {
				task: {
					id: null
				},
				history: null,
				notifications: null,
				refresh: null,
				loader: null,

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
			refreshTask: function(callback) {
				this.$http.post('/api/getTask/'+this.task.id, {
					days: this.chart.days
				})
				.then(response => {
					this.task 			= response.data.task
					this.history 		= response.data.history
					this.notifications	= response.data.notifications
					this.refreshGraph(response.data.stats)
					this.loader.hide()
				})
				.then(() => {
					if (this.refresh == null) {
						this.refresh = window.setInterval(() => {
							this.refreshTask()
						}, 10000)
					}
				})
				.then(() => {
					this.loader.hide()
				})
			},
			refreshGraph: function(stats) {
				let xaxis = [];
				let new_data_a = [];
				let new_data_b = [];

				for (let date in stats) {
					xaxis.push(date)
					new_data_a.push(stats[date]['up'])
					new_data_b.push(stats[date]['down'])
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
						height: 350,
						stacked: true,
						stackType: '100%'
					},
					legend: {
						position: 'right',
						offsetX: 0,
						offsetY: 50
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
			},
		},
		mounted: function() {
			this.loader = this.$loading.show()
			this.task.id = this.$route.params.id ?? null

			if (this.task.id != null) {
				this.refreshTask()
			}
		},
		beforeRouteLeave(to, from, next) {
			clearTimeout(this.refresh);
			next();
		},
    }
</script>

<style scoped>

</style>

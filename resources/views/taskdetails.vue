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
				v-model="days"
				@change="refreshTask"
			>
				<option value="7">7 days</option>
				<option value="15">15 days</option>
				<option value="30">30 days</option>
			</select>

			<!-- Uptime chart block -->
			<div id="chart" class="round">
				<h3>Last {{ days }} days uptime</h3>
				<div class="block-content">
					<apexchart class="graph" v-if="charts.uptime.render" type="bar" height="350" :options="charts.uptime.options" :series="charts.uptime.series"></apexchart>
					<p class="no-data" v-else>No chart to display here</p>
				</div>
			</div>

			<!-- Response time chart block -->
			<div id="chart" class="round" v-if="task.type == 'http'">
				<h3>Last {{ days }} days response time</h3>
				<div class="block-content">
					<apexchart class="graph" v-if="charts.response.render" type="line" height="350" :options="charts.response.options" :series="charts.response.series"></apexchart>
					<p class="no-data" v-else>No chart to display here</p>
				</div>
			</div>



			<!-- History backlog -->
			<div class="round">
				<h3>Last {{ days }} days history log</h3>
				<div class="block-content" v-if="history && Object.keys(history).length > 0">
					<p><i>Showing only records where status has changed</i></p>
					<table id="tasks_tbl">
						<thead>
							<tr>
								<th width="10%">Date</th>
								<th width="10%">Time</th>
								<th width="*">Output</th>
								<th width="10%">Duration</th>
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
								<td>
									<span v-if="h.duration != null">{{ h.duration+'s' }}</span>
									<span v-else><i>No duration</i></span>
								</td>
								<td :class="statusText(h.status)">
									<img :src="'/img/'+statusText(h.status)+'.svg'" width="16" alt="Status" />
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<p class="no-data" v-else>No history to display here</p>
			</div>

			<!-- Notifications block -->
			<div class="round">
				<h3>Last {{ days }} days notifications log</h3>
				<div class="block-content" v-if="notifications && Object.keys(notifications).length > 0">
					<table id="tasks_tbl">
						<thead>
							<tr>
								<th width="10%">Date</th>
								<th width="10%">Time</th>
								<th width="15">Firstname</th>
								<th width="15%">Lastname</th>
								<th width="30%">Email</th>
								<th width="10%">Type</th>
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
								<td>{{ n.task_history.status == 1 ? 'UP' : 'DOWN' }}</td>
								<td>{{ n.status.toUpperCase() }}</td>
							</tr>
						</tbody>
					</table>
				</div>
				<p class="no-data" v-else>No notification to display here</p>
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
				days: 7,
				first_day: null,

				charts: {
					uptime: {
						render: false,
					},
					response: {
						render: false,
					}
				}

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
					days: this.days
				})
				.then(response => {
					this.task 			= response.data.task
					this.history 		= response.data.history
					this.first_day		= new Date(response.data.first_day).getTime();
					this.notifications	= response.data.notifications
					this.refreshUptimeGraph(response.data.stats.uptime)

					if (this.task.type == 'http') {
						this.refreshResponseTimeGraph(response.data.stats.times)
					}

					this.loader.hide()
				})
				.then(() => {
					if (this.refresh == null) {
						this.refresh = window.setInterval(() => {
							this.refreshTask()
						}, 10000)
					}
				})
				.catch(error => {
					//TODO: do something
				})
				.then(() => {
					this.loader.hide()
				})
			},
			refreshResponseTimeGraph: function(stats) {
				let data = [];
				let xaxis = [];

				for (let date in stats) {
					xaxis.push(new Date(date).getTime())

					if (stats[date]['count'] > 0) {
						data.push(Math.round( (stats[date]['duration'] / stats[date]['count']) * 100) / 100)
					}
					else {
						data.push(0)
					}
				}

				this.charts.response.options = {
					xaxis: {
						type: 'datetime',
						//min: this.first_day,
						categories: xaxis,
						labels: {
							show: true,
							rotate: -45,
						}
					},
					yaxis: {
						labels: {
							formatter: function (value) {
								return (Math.round(value * 100) / 100) + "s";
							}
						}
					},
					tooltip: {
						x: {
							format: "dd MMM yyyy"
						}
					},
					chart: {
						type: 'line',
						height: 350,
						stacked: false
					},
					legend: {
						position: 'right',
						offsetX: 0,
						offsetY: 50
					},
					dataLabels: {
						enabled: true,
					},
					colors: ['#00955c'],
					stroke: {
						curve: 'smooth',
					},
					fill: {
						type: 'gradient',
						gradient: {
							//shade: 'dark',
							shadeIntensity: 1,
							type: 'vertical',
							opacityFrom: 1,
							opacityTo: 1,
							colorStops: [
							{
								offset: 20,
								color: "#FAD375",
								opacity: 1
							},
							{
								offset: 40,
								color: "#61DBC3",
								opacity: 1
							}
							]
						}
					}
				}
				this.charts.response.series = [{
					name: 'Response time',
					data: data
				}]

				this.charts.response.render = true
			},
			refreshUptimeGraph: function(stats) {
				let xaxis = [];
				let new_data_a = [];
				let new_data_b = [];

				for (let date in stats) {
					let total = stats[date]['up'] + stats[date]['down']

					xaxis.push(new Date(date).getTime())
					if (total > 0) {
						new_data_a.push( Math.round(stats[date]['up'] / total * 100) )
						new_data_b.push( Math.round(stats[date]['down'] / total * 100) )
					}
					else {
						new_data_a.push( 0 )
						new_data_b.push( 0 )
					}
				}

				this.charts.uptime.options = {
					xaxis: {
						type: 'datetime',
						min: this.first_day,
						categories: xaxis,
						tickAmount: 6,
						labels: {
							show: true,
							rotate: -45,
						}
					},
					yaxis: {
						labels: {
							formatter: function (value) {
								return value + "%";
							}
						}
					},
					tooltip: {
						x: {
							format: "yyyy MMM dd"
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
				this.charts.uptime.series = [{
					name: 'UP',
					data: new_data_a,
					color: '#00955c'
				},
				{
					name: 'DOWN',
					data: new_data_b,
					color: '#ef3232'
				}]

				this.charts.uptime.render = true
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

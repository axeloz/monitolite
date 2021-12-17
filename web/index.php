<?php require_once __DIR__.'/DB.php'; ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<title>MonitoLite - Network monitoring tool</title>
		<script
			  src="https://code.jquery.com/jquery-3.5.1.min.js"
			  integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
			  crossorigin="anonymous">
		</script>
		<script type="text/javascript" src="js/scripts.js"></script>
		<link type="text/css" rel="stylesheet" href="css/styles.css" />
	</head>

	<body>
		<h1>MonitoLite Dashboard</h1>	
		
		<?php if ($tasks = $db->get_all_tasks()): ?>
		
			<?php foreach ($tasks as $task): ?>
				<div class="task">
					<p class="exp-icon" title="Click here to expand/collapse the task">&nbsp;</p>
					<!--<p class="task-overlay"><img src="img/expand.png" width="32"></p>-->
					<h2>Task <small>#</small><?php echo $task['id']; ?> » <span class="highlight"><?php echo $task['type']; ?></span> for host <span class="highlight"><?php echo $task['host']; ?></span></h2>

					<table id="tasks_tbl">
						<thead>
							<tr>
								<th width="5%">Up?</th>
								<th width="*">Host</th>
								<th width="5%">Type</th>
								<th width="10%">Parameters</th>
								<th width="20%">Last execution</th>
								<th width="10%">Frequency (min)</th>
								<th width="5%">Active</th$query>
							</tr>
						</thead>
						<tbody>
						<?php 
							$status = $db->get_task_last_status($task['id']);
							$color = $status == 1 ? '#c9ecc9' : '#ffc5c5'; 
							$icon = $status == 1 ? 'up.png': 'down.png';
						?>
						<tr>
							<td style="background-color: <?php echo $color; ?>"><img src="img/<?php echo $icon; ?>" width="16" alt="Status" /></td>
							<td style="background-color: <?php echo $color; ?>"><?php echo $task['host']; ?></td>
							<td>
								<?php if ($task['type'] == 'http'): ?>
									<img src="img/http.png" width="16" alt="Warning" title="Type: <?php echo $task['type']; ?>"/>
								<?php elseif ($task['type'] == 'ping'): ?>
									<img src="img/ping.png" width="16" alt="Warning" title="Type: <?php echo $task['type']; ?>"/>
								<?php endif; ?>
							</td>
							<td><?php echo $task['params']; ?></td>
							<td><?php echo $task['last_execution']; ?></td>
							<td><?php echo ($task['frequency'] / 60); ?></td>
							<td><?php echo ($task['active'] == 1) ? 'Yes' : 'No'; ?></td>
						</tr>
						</tbody>
					</table>
				
					<div class="hidden">
						<div id="history">
							<h3>Task history</h3>
							<?php if ($histories = $db->get_all_history($task['id'], 5)): ?>
								<table id="history_tbl">
									<thead>
										<tr>
											<th>Datetime</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($histories as $history): ?>
										<?php
											$color = ($history['status'] == 1) ? '#c9ecc9' : '#ffc5c5';
										?>
										<tr>
											<td width="20%" align="center"><?php echo $history['datetime']; ?></td>

											<?php if ($history['status'] == 1): ?>
												<td width="20%" align="center" style="background-color:#c9ecc9;">
													<img src="img/success.png" width="16" alt="Success">&nbsp;SUCCESS
												</td>
											<?php else: ?>
												<td width="20%" align="center" style="background-color:#ffc5c5;">
													<img src="img/error.png" width="16" alt="Success">&nbsp;ERROR
												</td>
											<?php endif; ?>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
								<p><small>Only the 5 latest entries are displayed</small></p>
							<?php else: ?>
								<p class="no_result">No history found here</p>
							<?php endif; ?>
						</div>
						
						<div id="contacts">
							<h3>Task contacts</h3>

							<?php if ($contacts = $db->get_all_contacts($task['id'])): ?>
								<table id="contacts_tbl">
									<thead>
										<tr>
											<th>Surname</th>
											<th>Firstname</th>
											<th>Email</th>
											<th>Phone</th>
											<th>Creation date</th>
											<th>Active</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($contacts as $contact): ?>
										<tr>
											<td width="15%"><?php echo $contact['surname']; ?></td>
											<td width="15%"><?php echo $contact['firstname']; ?></td>
											<td width="20%"><?php echo $contact['email']; ?></td>
											<td width="15%"><?php echo $contact['phone']; ?></td>
											<td width="15%"><?php echo $contact['creation_date']; ?></td>
											<td width="15%"><?php echo ($contact['active'] == 1) ? 'Yes' : 'No'; ?></td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							<?php else: ?>
								<p class="no_result">
									<img src="img/warning.png" width="16" alt="Warning"/>
									No contact found here. That means that nobody will get any notification in case of an error.
								</p>
							<?php endif; ?>
						</div>
					</div>	
				</div>
			
			<?php endforeach; ?>
		
		<?php else: ?>
			<p class="no_result">No task found here</p>
		<?php endif; ?>

		</div>
	</body>
</html>

<?php require_once __DIR__.'/DB.php'; ?>

<html>
	<head>
		<script type="text/javascript" src="js/scripts.js"></script>
		<link type="text/css" rel="stylesheet" href="css/styles.css" />
	</head>

	<body>
		<h1>MonitoLite dashboard interface</h1>	
		
		<?php if ($tasks = $db->get_all_tasks()): ?>
		
			<?php foreach ($tasks as $task): ?>

				<div id="task">
					<h2>Task <small>#</small><?php echo $task['id']; ?> » <i>"<?php echo $task['type']; ?>"</i> for host <i>"<?php echo $task['host']; ?>"</i></h2>

					<table id="tasks_tbl">
						<thead>
							<tr>
								<th>Host</th>
								<th>Type</th>
								<th>Parameters</th>
								<th>Creation date</th>
								<th>Frequency (min)</th>
								<th>Last execution</th>
								<th>Active</th$query>
							</tr>
						</thead>
						<tbody>
						<?php 
							$status = $db->get_task_last_status($task['id']);
							$color = $status == 1 ? '#c9ecc9' : '#ffc5c5'; 
						?>
						<tr>
							<td><?php echo $task['host']; ?></td>
							<td><?php echo $task['type']; ?></td>
							<td><?php echo $task['params']; ?></td>
							<td><?php echo $task['creation_date']; ?></td>
							<td><?php echo ($task['frequency'] / 60); ?></td>
							<td style="background-color: <?php echo $color; ?>"><?php echo $task['last_execution']; ?></td>
							<td><?php echo ($task['active'] == 1) ? 'Yes' : 'No'; ?></td>
						</tr>
						</tbody>
					</table>
				
				
					<div id="history">
						<h3>Task's history</h3>
						<p>Only the 5 last history entries are displayed</p>
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
										<td width="20%" align="center" style="background-color:<?php echo $color; ?>"><?php echo $history['status'] == 1 ? 'SUCCESS' : 'ERROR'; ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php else: ?>
							<p class="no_result">No history found here</p>
						<?php endif; ?>	
					</div>		
					
					<div id="contacts">
						<h3>Task's contacts</h3>
						
						<?php if ($contacts = $db->get_all_contacts($task['id'])): ?>
							<table id="contacts_tbl">
								<thead>
									<tr>
										<th>#</th>
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
										<td><?php echo $contact['id']; ?></td>
										<td><?php echo $contact['surname']; ?></td>
										<td><?php echo $contact['firstname']; ?></td>
										<td><?php echo $contact['email']; ?></td>
										<td><?php echo $contact['phone']; ?></td>
										<td><?php echo $contact['creation_date']; ?></td>
										<td><?php echo ($contact['active'] == 1) ? 'Yes' : 'No'; ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php else: ?>
							<p class="no_result">
								<img src="img/warning.png" width="20" alt="Warning"/>
								No contact found here. That means that nobody will get any notification in case of an error.
							</p>
						<?php endif; ?>
					</div>	
				</div>						
			
			<?php endforeach; ?>
		
		<?php else: ?>
			<p class="no_result">No task found here</p>
		<?php endif; ?>

		</div>
	</body>
</html>

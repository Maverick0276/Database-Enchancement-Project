<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] == "admin") {
    include "DB_connection.php";
    
    // Query to get all employees with their task counts and task details
    $query = "SELECT u.id, u.full_name, u.username, COUNT(t.id) as task_count, 
              GROUP_CONCAT(t.title SEPARATOR ', ') as task_titles,
              GROUP_CONCAT(t.status SEPARATOR ', ') as task_statuses
              FROM users u
              LEFT JOIN tasks t ON u.id = t.assigned_to
              WHERE u.role = 'employee'
              GROUP BY u.id, u.full_name, u.username
              ORDER BY task_count DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get detailed tasks for each employee
    $detail_query = "SELECT u.id, u.full_name, t.id as task_id, t.title, t.description, 
                     t.due_date, t.status, t.created_at
                     FROM users u
                     LEFT JOIN tasks t ON u.id = t.assigned_to
                     WHERE u.role = 'employee'
                     ORDER BY u.full_name, t.created_at DESC";
    
    $detail_stmt = $conn->prepare($detail_query);
    $detail_stmt->execute();
    $all_tasks = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group tasks by employee
    $employees_tasks = [];
    foreach ($all_tasks as $task) {
        $emp_id = $task['id'];
        if (!isset($employees_tasks[$emp_id])) {
            $employees_tasks[$emp_id] = [
                'id' => $emp_id,
                'full_name' => $task['full_name'],
                'tasks' => []
            ];
        }
        if ($task['task_id'] != null) {
            $employees_tasks[$emp_id]['tasks'][] = $task;
        }
    }
    
 ?>
<!DOCTYPE html>
<html>
<head>
	<title>Employee Tasks Amount</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/style.css">
	<style>
		.employee-card {
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 20px;
			margin: 15px 0;
			box-shadow: 0 2px 5px rgba(0,0,0,0.1);
		}
		.employee-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			border-bottom: 2px solid #007bff;
			padding-bottom: 10px;
			margin-bottom: 15px;
		}
		.employee-name {
			font-size: 18px;
			font-weight: bold;
			color: #333;
		}
		.task-count {
			background: #007bff;
			color: white;
			padding: 5px 15px;
			border-radius: 20px;
			font-weight: bold;
		}
		.task-list {
			list-style: none;
			padding: 0;
		}
		.task-item {
			background: white;
			padding: 10px;
			margin: 8px 0;
			border-left: 4px solid #28a745;
			border-radius: 4px;
		}
		.task-item.pending {
			border-left-color: #ffc107;
		}
		.task-item.in_progress {
			border-left-color: #0dcaf0;
		}
		.task-item.completed {
			border-left-color: #28a745;
		}
		.task-title {
			font-weight: bold;
			color: #333;
		}
		.task-status {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 4px;
			font-size: 12px;
			margin-top: 5px;
		}
		.status-pending {
			background: #ffc107;
			color: white;
		}
		.status-in_progress {
			background: #0dcaf0;
			color: white;
		}
		.status-completed {
			background: #28a745;
			color: white;
		}
		.no-tasks {
			color: #999;
			font-style: italic;
		}
		.summary-stats {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 15px;
			margin: 20px 0;
		}
		.stat-box {
			background: white;
			padding: 15px;
			border-radius: 8px;
			text-align: center;
			box-shadow: 0 2px 5px rgba(0,0,0,0.1);
		}
		.stat-number {
			font-size: 28px;
			font-weight: bold;
			color: #007bff;
		}
		.stat-label {
			color: #666;
			font-size: 14px;
		}
	</style>
</head>
<body>
	<input type="checkbox" id="checkbox">
	<?php include "inc/header.php" ?>
	<div class="body">
		<?php include "inc/nav.php" ?>
		<section class="section-1">
			<h4 class="title-2">Employee Tasks Summary</h4>
			
			<!-- Summary Statistics -->
			<div class="summary-stats">
				<div class="stat-box">
					<div class="stat-number"><?php echo count($employees_tasks); ?></div>
					<div class="stat-label">Total Employees</div>
				</div>
				<div class="stat-box">
					<div class="stat-number">
						<?php 
						$total_tasks = 0;
						foreach ($employees_tasks as $emp) {
							$total_tasks += count($emp['tasks']);
						}
						echo $total_tasks;
						?>
					</div>
					<div class="stat-label">Total Tasks</div>
				</div>


                
				<div class="stat-box">
					<div class="stat-number">
						<?php 
						$avg_tasks = count($employees_tasks) > 0 ? round($total_tasks / count($employees_tasks), 1) : 0;
						echo $avg_tasks;
						?>
					</div>
					<div class="stat-label">Avg Tasks per Employee</div>
				</div>
			</div>

			<!-- Employee Tasks Display -->
			<?php if (count($employees_tasks) > 0) { ?>
				<?php foreach ($employees_tasks as $employee) { ?>
					<div class="employee-card">
						<div class="employee-header">
							<span class="employee-name">
								<i class="fa fa-user"></i> <?php echo $employee['full_name']; ?>
							</span>
							<span class="task-count"><?php echo count($employee['tasks']); ?> Task<?php echo count($employee['tasks']) != 1 ? 's' : ''; ?></span>
						</div>
						
						<?php if (count($employee['tasks']) > 0) { ?>
							<ul class="task-list">
								<?php foreach ($employee['tasks'] as $task) { ?>
									<li class="task-item <?php echo $task['status']; ?>">
										<div class="task-title"><?php echo $task['title']; ?></div>
										<div><?php echo $task['description']; ?></div>
										<div>
											<small>Due: <?php echo $task['due_date'] ? $task['due_date'] : 'No Deadline'; ?></small>
											<span class="task-status status-<?php echo $task['status']; ?>">
												<?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
											</span>
										</div>
									</li>
								<?php } ?>
							</ul>
						<?php } else { ?>
							<p class="no-tasks">No tasks assigned</p>
						<?php } ?>
					</div>
				<?php } ?>
			<?php } else { ?>
				<h3>No employees found</h3>
			<?php } ?>
			
		</section>
	</div>

<script type="text/javascript">
	var active = document.querySelector("#navList li:nth-child(6)");
	active.classList.add("active");
</script>
</body>
</html>
<?php }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
 ?>

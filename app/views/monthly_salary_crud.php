<?php 
    // Assuming 'header.php' includes session_start() and other essentials
    include_once('header.php'); 
    include_once('navbar.php');
    
    // Convert selected month for display title
    $display_title_month = date('F Y', strtotime($selected_month_db_format));
?>

<div class="container-fluid mt-3">
    <div class="col-md-12">
        <h1 class="display-5 fw-bold">Monthly Salary Management</h1>
        
        <?php if (!empty($status_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $status_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- MONTH SELECTION FORM -->
    <form action="monthly_salary_crud" method="GET">
        <div class="row align-items-end mb-4">
            
            <div class="col-auto">
                <label for="inputMonth" class="form-label">Select Month:</label>
                <!-- Input type="month" uses YYYY-MM format -->
                <input type="month" class="form-control" id="inputMonth" name="month" value="<?php echo htmlspecialchars($selected_year_month_display); ?>" required>
            </div>
            <div class="col-auto">
                <!-- Submit button for the input[type=month] selection -->
                <button type="submit" class="btn btn-primary">Go</button>
            </div>

            <!-- Previous Month Button -->
            <div class="col-auto">
                <a href="monthly_salary_crud?month=<?php echo htmlspecialchars($prev_month); ?>" class="btn btn-outline-secondary">
                    &lt; Prev Month
                </a>
            </div>
       
            <!-- Next Month Button -->
            <div class="col-auto">
                <?php 
                    // Disable the next button if the currently selected month is the current month
                    $next_disabled_class = ($selected_year_month_display >= $current_year_month) ? 'disabled' : ''; 
                ?>
                <a href="monthly_salary_crud?month=<?php echo htmlspecialchars($next_month); ?>" class="btn btn-outline-secondary <?= $next_disabled_class ?>">
                    Next Month &gt;
                </a>
            </div>
            
        </div>
    </form>

    <hr>
    
    <!-- MAIN SALARY INPUT FORM -->
    <form id="salary-form" action="monthly_salary_crud" method="POST">
    
        <input type="hidden" name="month" value="<?php echo htmlspecialchars($selected_year_month_display); ?>">
        <input type="hidden" name="save_salaries" value="1">
        
        <div class="table-responsive sheet-container">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width: 5%;">Sr No.</th>
                        <th class="name-col" style="width: 50%;">Employee Name</th>
                        <th style="width: 45%;">Monthly Salary (INR) for <?= $display_title_month ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr><td colspan="3" class="text-center">No employees found in the system.</td></tr>
                    <?php else: ?>
                        <?php $i = 1; ?>
                        <?php foreach ($employees as $row): ?>
                            <?php 
                                $employee_id = $row->employee_id;
                                $current_salary = $salary_map[$employee_id] ?? 0.00;
                                
                                // Display the salary value formatted to two decimal places, including 0.00.
                                $display_value = number_format($current_salary, 2, '.', '');
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row->employee_name); ?>
                                    <?php if (isset($row->active) && $row->active == 0): ?>
                                        <span class="badge bg-danger text-white ml-2">INACTIVE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input 
                                        type='number' 
                                        class='form-control salary-input-field' 
                                        name='salaries[<?php echo $employee_id; ?>]' 
                                        value="<?php echo $display_value; ?>" 
                                        step="0.01"
                                        min="0"
                                        placeholder="Enter salary (e.g., 50000.00)"
                                    >
                                </td>
                            </tr>

                        <?php 
                            $i++;
                            endforeach; 
                        ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <button type="submit" class="btn btn-success btn-lg mt-3">💾 Save All Monthly Salaries for <?= $display_title_month ?></button>
    </form>
</div>


<?php
    include_once('footer.php');
?>

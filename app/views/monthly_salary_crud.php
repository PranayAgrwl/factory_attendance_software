<?php 
    // Assuming 'header.php' includes session_start() and other essentials
    include_once('header.php'); 
    include_once('navbar.php');
    
    // Convert selected month for display title
    // This variable comes from the Controller
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

    <form action="monthly_salary_crud" method="GET">
        <div class="row align-items-end mb-4">
            <div class="col-auto">
                <label for="month-select" class="form-label">Select Month:</label>
                <select name="month" id="month-select" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($month_list as $month_str): ?>
                        <?php 
                            $display_month = date('F Y', strtotime($month_str . '-01'));
                            $selected = ($month_str === $selected_year_month_display) ? 'selected' : '';
                        ?>
                        <option value="<?= $month_str ?>" <?= $selected ?>>
                            <?= $display_month ?>
                            <?php 
                            // Add "(Saved)" status to months that aren't currently selected, but exist in the list.
                            if ($selected_year_month_display !== $month_str && in_array($month_str, $month_list)): 
                            ?>
                                (Saved)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Go</button>
            </div>
            
            <!-- <div class="col-auto">
                <span class="text-muted small align-middle" style="padding-top: 5px;">
                    (Data is editable directly in the table below.)
                </span>
            </div> -->
        </div>
    </form>

    <hr>
    
    <form action="monthly_salary_crud" method="POST">
    
        <input type="hidden" name="month" value="<?php echo htmlspecialchars($selected_year_month_display); ?>">
        <input type="hidden" name="save_salaries" value="1">
    
        <div class="table-responsive sheet-container">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width: 5%;">Sr No.</th>
                        <th class="name-col" style="width: 50%;">Employee Name</th>
                        <th style="width: 45%;">Monthly Salary (INR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr><td colspan="3" class="text-center">No employees found in the system.</td></tr>
                    <?php else: ?>
                        <?php $i = 1; ?>
                        <?php foreach ($employees as $row): ?>
                            <?php 
                                // Employee data is mapped by employee_id, which we access here.
                                $employee_id = $row->employee_id;
                                // Get existing value from the map or default to 0.00
                                $current_salary = $salary_map[$employee_id] ?? 0.00;
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
                                        class='form-control' 
                                        name='salaries[<?php echo $employee_id; ?>]' 
                                        value="<?php echo number_format($current_salary, 2, '.', ''); ?>" 
                                        step="0.01"
                                        min="0"
                                        required
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

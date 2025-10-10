<?php 
    include_once('header.php');
    include_once('navbar.php');
?>


<div class="container-fluid mt-3">
    <div class="col-md-8">
        <h1 class="display-5 fw-bold">Daily Attendance Report</h1>
        <?php if (isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Attendance entries for <?php echo htmlspecialchars($selected_date); ?> saved successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <form action="attendance" method="GET">
        <div class="row align-items-end mb-4">
            
            <div class="col-auto">
                <label for="inputDate" class="form-label">Select Date:</label>
                <input type="date" class="form-control" id="inputDate" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Go</button>
            </div>


            <!-- NEW: Previous Date Button -->
            <div class="col-auto">
                <a href="attendance?date=<?php echo htmlspecialchars($prev_date); ?>" class="btn btn-outline-secondary">
                    &lt; Prev Date
                </a>
            </div>
            
            <!-- NEW: Next Date Button -->
            <div class="col-auto">
                <a href="attendance?date=<?php echo htmlspecialchars($next_date); ?>" class="btn btn-outline-secondary">
                    Next Date &gt;
                </a>
            </div>
        </div>
    </form>

    <hr>
    
    <form action="attendance" method="POST">
    
        <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
        <input type="hidden" name="save_attendance" value="1">
    
        <div class="table-responsive sheet-container">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width: 5%;">Sr No.</th>
                        <th class="name-col">Employee Name</th>
                        <th style="width: 20%;">Daily Attendance (in shifts)</th>
                        <th style="width: 20%;">Extra Work (in rs)</th>
                        <th style="width: 20%;">Advance Taken (in rs)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($employee_names)) {
                        $i = 1;
                        foreach ($employee_names as $row) {
                            // Check if attendance data exists for this employee and date
                            $attendance = $attendance_map[$row->employee_id] ?? null;

                            // 👇 NEW: Check if the employee should be displayed
                            // Display IF: 1. They are currently active (row->active == 1) 
                            // OR 2. An attendance record exists for this date ($attendance is not null)
                            if ($row->active != 1 && $attendance === null) {
                                continue; // Skip this employee if they are inactive AND have no record for this date
                            }
                            // 👆 END NEW

                            // Get existing values or default to empty/zero
                            $daily_attendance = $attendance ? htmlspecialchars($attendance->daily_attendance) : '';
                            $extra_work = $attendance ? htmlspecialchars($attendance->extra_work) : '';
                            $advance_taken = $attendance ? htmlspecialchars($attendance->advance_taken) : '';
                    ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td>
                            <?php echo htmlspecialchars($row->employee_name); ?>
                            <!-- <input type="hidden" name="employee_id" value="<+php echo ($row->employee_id); ?>"> -->
                        </td>
                        <td>
                            <input type='number' class='form-control' name='entries[<?php echo $row->employee_id; ?>][attendance]' value="<?php echo $daily_attendance; ?>" step="0.01">
                        </td>
                        <td>
                            <input type='number' class='form-control' name='entries[<?php echo $row->employee_id; ?>][extra_work]' value="<?php echo $extra_work; ?>" step="0.01">
                        </td>
                        <td>
                            <input type='number' class='form-control'name='entries[<?php echo $row->employee_id; ?>][advance_taken]' value="<?php echo $advance_taken; ?>">
                        </td>
                    </tr>

                    <?php 
                        $i++;
                        } 
                    } else {
                        // Display a message if no employees are found
                        echo '<tr><td colspan="5">No row data found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <button type="submit" class="btn btn-success btn-lg mt-3">💾 Save All Entries</button>
    </form>
</div>



<?php
    include_once('footer.php');
?>

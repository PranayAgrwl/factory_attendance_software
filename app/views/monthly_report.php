<?php 
    include_once('header.php');
    include_once('navbar.php');
?>

<div class="container-fluid mt-3">
    <div class="col-md-12">
        <h1 class="display-5 fw-bold">Monthly Attendance Report for: <?php echo htmlspecialchars($selected_year_month); ?></h1>
    </div>

    <form action="monthly_report" method="GET">
        <div class="row align-items-end mb-4">
            <div class="col-auto">
                <label for="inputMonth" class="form-label">Select Month:</label>
                <input type="month" class="form-control" id="inputMonth" name="month" value="<?php echo htmlspecialchars($selected_year_month); ?>" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
        </div>
    </form>
    
    <hr>
    
    <div class="table-responsive sheet-container">
        <table class="table table-bordered table-hover align-middle monthly-report-table">
            <thead>
                <tr>
                    <th rowspan="2" style="min-width: 150px;">Employee Name</th>
                    <th rowspan="2" style="min-width: 120px;">Metric</th>
                    <th colspan="<?php echo $days_in_month; ?>" class="text-center">Daily Data (by Date)</th>
                    <th colspan="3" class="text-center">Monthly Summary</th>
                </tr>
                <tr>
                    <?php 
                        // Generate table headers for each day of the month
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            echo "<th>" . $d . "</th>";
                        }
                    ?>
                    <th>Total Shifts</th>
                    <th>Extra Shifts (Rs)</th>
                    <th>Total Advance (Rs)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($employee_names)) {
                    foreach ($employee_names as $employee) {
                        $employee_id = $employee->employee_id;
                        $report = $monthly_report_map[$employee_id] ?? null;

                        // Only show the employee if they have records OR are currently active
                        if ($report === null && $employee->active != 1) {
                            continue;
                        }

                        // Get monthly totals (for the final three summary columns)
                        $total_shifts = $report['total_shifts'] ?? 0;
                        $total_extra = $report['total_extra'] ?? 0.00;
                        $total_advance = $report['total_advance'] ?? 0.00;

                        // Define the metrics we want to display in the three rows
                        $metrics = [
                            'shifts' => ['label' => 'Attendance', 'total' => $total_shifts, 'column' => 'daily_attendance', 'format' => ''],
                            'extra' => ['label' => 'Extra Work', 'total' => $total_extra, 'column' => 'extra_work', 'format' => 'number_format'],
                            'advance' => ['label' => 'Advance Taken', 'total' => $total_advance, 'column' => 'advance_taken', 'format' => 'number_format']
                        ];
                        
                        // Start an iteration counter for row styling/grouping
                        $metric_counter = 0;
                        
                        // Loop to print the three required rows for this one employee
                        foreach($metrics as $metric_key => $metric_data)
                        {
                            $metric_counter++;
                            $row_class = ($metric_counter === 1) ? 'employee-group-start' : ''; // Optional class for visual grouping
                            
                            echo "<tr class='{$row_class}'>";
                            
                            // 1. Employee Name (only prints on the first row, spans 3 rows)
                            if ($metric_counter === 1) {
                                echo "<td rowspan='3' class='fw-bold'>" . htmlspecialchars($employee->employee_name) . "</td>";
                            }

                            // 2. Metric Label
                            echo "<td class='text-start'>" . $metric_data['label'] . "</td>";

                            // 3. Daily Data (Loop through all days of the month)
                            for ($d = 1; $d <= $days_in_month; $d++) {
                                $date_key = $selected_year_month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                                $daily_value = 0;
                                
                                if (isset($report['dates'][$date_key])) {
                                    // Get the specific value (daily_attendance, extra_work, or advance_taken)
                                    $col_name = $metric_data['column'];
                                    $daily_value = $report['dates'][$date_key]->$col_name;
                                }
                                
                                // Display the value, using '-' for zero values to keep it clean
                                $display_value = ($daily_value > 0) ? $daily_value : '-';
                                
                                echo "<td class='text-center'>" . $display_value . "</td>";
                            }

                            // 4. Monthly Summary (Prints the totals, only once per metric type)
                            if ($metric_key === 'shifts') {
                                echo "<td rowspan='3' class='text-center fw-bold'>" . $total_shifts . "</td>";
                            // }
                            // if ($metric_key === 'extra') {
                                echo "<td rowspan='3' class='text-end fw-bold'>" . number_format($total_extra, 2) . "</td>";
                            // }
                            // if ($metric_key === 'advance') {
                                echo "<td rowspan='3' class='text-end fw-bold'>" . number_format($total_advance, 2) . "</td>";
                            }

                            echo "</tr>";
                        }
                    } 
                } else {
                    // +5 columns: Employee Name, Metric, Total Shifts, Total Extra, Total Advance
                    echo '<tr><td colspan="' . ($days_in_month + 5) . '">No employee data found for the selected criteria.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
    include_once('footer.php');
?>
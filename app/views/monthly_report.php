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
                    <th>Total Earnings (Shifts x Salary)</th>
                    <th>Extra Work (₹)</th>
                    <th>Advance Taken (₹)</th>
                    <th>NET DUE (₹)</th>
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

                        // --- Data Aggregation ---
                        $total_shifts = $report['total_shifts'] ?? 0;
                        $total_extra = $report['total_extra'] ?? 0.00;
                        $total_advance = $report['total_advance'] ?? 0.00;
                        $total_earnings = 0.00; // Recalculate based on snapshot
                        
                        // Calculate total earnings using the salary snapshot from each daily record
                        if ($report && isset($report['dates'])) {
                            foreach ($report['dates'] as $daily_record) {
                                // IMPORTANT: Use the recorded salary_snapshot for the calculation
                                $daily_salary = (float)($daily_record->salary_snapshot ?? 0.00);
                                $total_earnings += ((float)$daily_record->daily_attendance * $daily_salary);
                            }
                        }
                        
                        // CALCULATION: Net Due
                        $net_due = $total_earnings + $total_extra - $total_advance;
                        // ------------------------


                        // Define the metrics we want to display in the three rows
                        // CHANGED: The 'shifts' metric label is now 'Earning'
                        $metrics = [
                            'shifts' => ['label' => 'Daily Earning', 'total' => $total_earnings, 'column' => 'daily_attendance', 'is_earning' => true],
                            'extra' => ['label' => 'Extra Work', 'total' => $total_extra, 'column' => 'extra_work', 'is_earning' => false],
                            'advance' => ['label' => 'Advance Taken', 'total' => $total_advance, 'column' => 'advance_taken', 'is_earning' => false]
                        ];
                        
                        $metric_counter = 0;
                        
                        // Loop to print the three required rows for this one employee
                        foreach($metrics as $metric_key => $metric_data)
                        {
                            $metric_counter++;
                            $row_class = ($metric_counter === 1) ? 'employee-group-start' : ''; 
                            
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
                                $display_value = '-';
                                
                                if (isset($report['dates'][$date_key])) {
                                    $record = $report['dates'][$date_key];
                                    $col_name = $metric_data['column'];
                                    $daily_value = (float)($record->$col_name ?? 0.00);

                                    if ($metric_data['is_earning']) {
                                        // NEW LOGIC: Calculate Daily Earning (Attendance x Snapshot Salary)
                                        $daily_salary = (float)($record->salary_snapshot ?? 0.00);
                                        $daily_earning = $daily_value * $daily_salary;
                                        $display_value = ($daily_earning > 0) ? number_format($daily_earning, 2) : '-';
                                    } else {
                                        // Existing logic for Extra Work and Advance Taken
                                        $display_value = ($daily_value > 0) ? number_format($daily_value, 2) : '-';
                                    }
                                }
                                
                                echo "<td class='text-center'>" . $display_value . "</td>";
                            }

                            // 4. Monthly Summary (Prints the totals, spans 3 rows)
                            if ($metric_counter === 1) {
                                
                                // Total Earnings (Shifts x Salary)
                                echo "<td rowspan='3' class='text-end fw-bold bg-light'>" . number_format($total_earnings, 2) . "</td>";
                                
                                // Total Extra Work
                                echo "<td rowspan='3' class='text-end fw-bold'>" . number_format($total_extra, 2) . "</td>";
                                
                                // Total Advance
                                echo "<td rowspan='3' class='text-end fw-bold'>" . number_format($total_advance, 2) . "</td>";
                                
                                // NET DUE - NEW COLUMN (Final Result)
                                echo "<td rowspan='3' class='text-end fw-bold bg-light'>" . number_format($net_due, 2) . "</td>";
                            }

                            echo "</tr>";
                        }
                    } 
                } else {
                    // colspan is days_in_month + 5 (Employee Name, Metric, Earnings, Extra, Advance, Due)
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
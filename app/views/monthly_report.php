<?php 
    include_once('header.php');
    include_once('navbar.php');
?>

<style>
    .sheet-container {
        overflow-x: auto;
        max-width: 100%;
        margin-bottom: 2rem;
    }
    .monthly-report-table {
        min-width: 1000px; /* Ensure table is wide enough to scroll */
    }
    .monthly-report-table th, .monthly-report-table td {
        white-space: nowrap;
        text-align: center;
        padding: 0.5rem;
    }
    .monthly-report-table thead th {
        vertical-align: middle;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    /* Style for the start of a new employee group */
    .employee-group-start {
        border-top: 3px solid #000;
    }
</style>

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

            <!-- Previous Month Button -->
            <div class="col-auto">
                <a href="monthly_report?month=<?php echo htmlspecialchars($prev_month); ?>" class="btn btn-outline-secondary">
                    &lt; Prev Month
                </a>
            </div>
   
            <!-- Next Month Button -->
            <div class="col-auto">
                <a href="monthly_report?month=<?php echo htmlspecialchars($next_month); ?>" class="btn btn-outline-secondary">
                    Next Month &gt;
                </a>
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
                    <th colspan="4" class="text-center">Monthly Summary</th> 
                </tr>
                <tr>
                    <?php 
                        // Generate table headers for each day of the month
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            echo "<th>" . $d . "</th>";
                        }
                    ?>
                    <th>Total Earnings (₹)</th>
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

                        // NEW: Get the fixed daily wage for this month from the map
                        $daily_wage = $monthly_salary_map[$employee_id] ?? 0.00;

                        // Only show the employee if they have records OR are currently active OR have a daily wage set
                        if ($report === null && $employee->active != 1 && $daily_wage == 0.00) {
                            continue;
                        }

                        // --- Data Aggregation ---
                        $total_shifts = $report['total_shifts'] ?? 0;
                        $total_extra = $report['total_extra'] ?? 0.00;
                        $total_advance = $report['total_advance'] ?? 0.00;

                        // Calculate total earnings: Total Shifts * Daily Wage (from monthwise_salary table)
                        $total_earnings = $total_shifts * $daily_wage; 
                        
                        // CALCULATION: Net Due
                        $net_due = $total_earnings + $total_extra - $total_advance;
                        // ------------------------


                        // Define the metrics we want to display in the three rows
                        $metrics = [
                            // Daily Earning is calculated by Daily Attendance * Daily Wage
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
                                
                                // Default to dash if no data is found for the day
                                $display_value = '-'; 

                                if (isset($report['dates'][$date_key])) {
                                    $record = $report['dates'][$date_key];
                                    $col_name = $metric_data['column'];
                                    $daily_value = 0.00;
                                    
                                    if ($metric_data['is_earning']) {
                                        // CORRECTED LOGIC: Calculate Daily Earning (Attendance x Fixed Daily Wage for the month)
                                        $daily_attendance = (float)($record->daily_attendance ?? 0.00);
                                        $daily_value = $daily_attendance * $daily_wage; // *** USES FIXED $daily_wage ***
                                    } else {
                                        // Logic for Extra Work and Advance Taken
                                        $daily_value = (float)($record->$col_name ?? 0.00);
                                    }
                                    
                                    // Check if value is > 0 or if there is a record for 0
                                    if ($daily_value > 0) {
                                        $display_value = number_format($daily_value, 2);
                                    } else if (isset($record->$col_name) && $daily_value == 0.00) {
                                        // If record exists but value is 0, display '0.00'
                                        $display_value = '0.00'; 
                                    } else {
                                        // If no record exists, keep it as '-'
                                        $display_value = '-';
                                    }
                                }
                                
                                echo "<td class='text-center'>" . $display_value . "</td>";
                            }

                            // 4. Monthly Summary (Prints the totals, spans 3 rows)
                            if ($metric_counter === 1) {
                                
                                // Total Earnings (Shifts x Salary)
                                echo "<td rowspan='3' class='text-end fw-bold'>" . number_format($total_earnings, 2) . "</td>";
                                
                                // Total Extra Work
                                echo "<td rowspan='3' class='text-end fw-bold bg-light'>" . number_format($total_extra, 2) . "</td>";
                                
                                // Total Advance
                                echo "<td rowspan='3' class='text-end fw-bold'>" . number_format($total_advance, 2) . "</td>";
                                
                                // NET DUE - NEW COLUMN (Final Result), highlight if negative
                                $net_due_class = ($net_due < 0) ? 'text-danger' : 'text-success';
                                echo "<td rowspan='3' class='text-end fw-bold bg-light {$net_due_class}'>" . number_format($net_due, 2) . "</td>";
                            }

                            echo "</tr>";
                        }
                    } 
                } else {
                    // colspan is days_in_month + 6 (Employee Name, Metric, Days, Earnings, Extra, Advance, Due)
                    echo '<tr><td colspan="' . ($days_in_month + 6) . '">No employee data found for the selected criteria.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
    include_once('footer.php');
?>

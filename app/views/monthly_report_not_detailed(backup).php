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
                    <th rowspan="2" style="min-width: 200px;">Employee Name</th>
                    <th colspan="<?php echo $days_in_month; ?>" class="text-center">Daily Attendance (Shifts)</th>
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
                    <th>Total Extra (Rs)</th>
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

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($employee->employee_name) . "</td>";

                        // Daily Shifts (Loop through all days of the month)
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            // Format the full date (YYYY-MM-DD)
                            $date_key = $selected_year_month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                            
                            $shift_count = 0;
                            if (isset($report['dates'][$date_key])) {
                                $shift_count = $report['dates'][$date_key]->daily_attendance;
                            }
                            
                            // Display the shift count (0 or a number)
                            echo "<td class='text-center'>" . ($shift_count > 0 ? $shift_count : '-') . "</td>";
                        }

                        // Monthly Totals Summary
                        $total_shifts = $report['total_shifts'] ?? 0;
                        $total_extra = $report['total_extra'] ?? 0.00;
                        $total_advance = $report['total_advance'] ?? 0.00;

                        echo "<td>" . $total_shifts . "</td>";
                        echo "<td>" . number_format($total_extra, 2) . "</td>";
                        echo "<td>" . number_format($total_advance, 2) . "</td>";
                        echo "</tr>";
                    } 
                } else {
                    echo '<tr><td colspan="' . ($days_in_month + 4) . '">No employee data found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
    include_once('footer.php');
?>
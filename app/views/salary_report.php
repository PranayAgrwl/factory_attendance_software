<?php 
    include_once('header.php');
    include_once('navbar.php');
?>

<div class="container-fluid mt-3">
    <div class="col-md-12">
        <h1 class="display-5 fw-bold">Salary Payout Report for: <?php echo htmlspecialchars($selected_year_month); ?></h1>
    </div>

    <form action="salary_report" method="GET">
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
                <a href="salary_report?month=<?php echo htmlspecialchars($prev_month); ?>" class="btn btn-outline-secondary">
                    &lt; Prev Month
                </a>
            </div>

            <!-- Next Month Button -->
            <div class="col-auto">
                <a href="salary_report?month=<?php echo htmlspecialchars($next_month); ?>" class="btn btn-outline-secondary">
                    Next Month &gt;
                </a>
            </div>
            
            <!-- EDITED: Now clearly exports CSV -->
            <div class="col-auto">
                <a 
                    href="export_salary_report?month=<?php echo htmlspecialchars($selected_year_month); ?>" 
                    class="btn btn-success"
                >
                    Export to CSV
                </a>
            </div>
            
            <!-- NEW: Button for XLSX Export -->
            <div class="col-auto">
                <a 
                    href="export_salary_report_xlsx?month=<?php echo htmlspecialchars($selected_year_month); ?>" 
                    class="btn btn-info text-white "
                >
                    Export to Excel (.XLSX)
                </a>
            </div>
        </div>
    </form>

    
    <hr>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr class="table-primary">
                    <th style="min-width: 200px;">Employee Name</th>
                    <th>Bank A/C Number</th>
                    <th>Bank IFSC Code</th>
                    <!-- Updated column header to reflect the filter -->
                    <th class="text-end" style="min-width: 150px;">Net Payable (₹)</th> 
                </tr>
            </thead>
            <tbody>
                <?php
                // Use the grand total passed from the controller (this total includes all employees, positive/negative)
                $grand_total = $total_payable_grand_total ?? 0.00;
                $employees_displayed = 0; // Tracks if the main loop runs

                if (!empty($salary_details)) {
                    
                    foreach ($salary_details as $detail) {
                        
                        // *** CRITICAL CHANGE: Only display records where Net Due is greater than 0 ***
                        if ($detail->net_due <= 0.00) {
                            continue; 
                        }
                        // *************************************************************************
                        
                        $employees_displayed++; 

                        // Note: Conditional coloring/row classes are removed to keep the table clean for Payouts
                        $text_class = '';
                        $row_class = '';
                ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="fw-bold"><?php echo htmlspecialchars($detail->employee_name); ?></td>
                        <td><?php echo htmlspecialchars($detail->bank_ac_number); ?></td>
                        <td><?php echo htmlspecialchars($detail->bank_ifsc_code); ?></td>
                        <td class="text-end fw-bold <?php echo $text_class; ?>"><?php echo number_format($detail->net_due, 2); ?></td>
                    </tr>
                <?php
                    } 
                    
                    // Display the Grand Total if any employee rows were shown (which should be true if $salary_details is not empty)
                    if ($employees_displayed > 0) { 
                        
                        // Grand Total coloring
                        $total_text_class = ($grand_total > 0) ? '' : (($grand_total < 0) ? '' : '');
                        $total_row_class = ($grand_total < 0) ? '' : '';

                ?>
                    <tr class="<?php echo $total_row_class; ?> fw-bold">
                        <!-- Note: The displayed GRAND NET BALANCE is for ALL employees, not just the filtered list. -->
                        <td colspan="3" class="text-end">GRAND NET BALANCE (ALL EMPLOYEES):</td>
                        <td class="text-end <?php echo $total_text_class; ?>"><?php echo number_format($grand_total, 2); ?></td>
                    </tr>
                <?php
                    }
                } 
                
                // If no employee data was found at all
                if ($employees_displayed === 0) {
                    echo '<tr><td colspan="4" class="text-center">No payable salaries found for this month, or no employee data exists.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
    include_once('footer.php');
?>

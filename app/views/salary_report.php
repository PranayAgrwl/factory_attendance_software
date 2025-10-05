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
            
            <div class="col-auto">
                <a 
                    href="export_salary_report?month=<?php echo htmlspecialchars($selected_year_month); ?>" 
                    class="btn btn-success"
                >
                    Export to Excel (.CSV)
                </a>
            </div>
            </div>
    </form>

<!-- // ... rest of the view ... -->
    
    <hr>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr class="table-primary">
                    <th style="min-width: 200px;">Employee Name</th>
                    <th>Bank A/C Number</th>
                    <th>Bank IFSC Code</th>
                    <th class="text-end" style="min-width: 150px;">Net Due (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Use the grand total passed from the controller
                $grand_total = $total_payable_grand_total ?? 0.00;
                $employees_displayed = 0; // Tracks if the main loop runs

                if (!empty($salary_details)) {
                    
                    foreach ($salary_details as $detail) {
                        
                        // FIX: Remove the > 0.00 filter to show ALL balances
                        $employees_displayed++; 

                        // Conditional coloring: green for payable, red for recoverable/negative
                        // $text_class = ($detail->net_due > 0) ? 'text-success' : (($detail->net_due < 0) ? 'text-danger' : 'text-muted');
                        $text_class = ($detail->net_due > 0) ? '' : (($detail->net_due < 0) ? '' : '');
                        
                        // Conditional row highlight for negative balances
                        // $row_class = ($detail->net_due < 0) ? 'table-warning' : '';
                        $row_class = ($detail->net_due < 0) ? '' : '';
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
                        // $total_text_class = ($grand_total > 0) ? 'text-success' : (($grand_total < 0) ? 'text-danger' : 'text-muted');
                        // $total_row_class = ($grand_total < 0) ? 'table-danger' : 'table-info';

						$total_text_class = ($grand_total > 0) ? '' : (($grand_total < 0) ? '' : '');
                        $total_row_class = ($grand_total < 0) ? '' : '';

                ?>
                    <tr class="<?php echo $total_row_class; ?> fw-bold">
                        <td colspan="3" class="text-end">GRAND NET BALANCE:</td>
                        <td class="text-end <?php echo $total_text_class; ?>"><?php echo number_format($grand_total, 2); ?></td>
                    </tr>
                <?php
                    }
                } 
                
                // If no employee data was found at all
                if (empty($salary_details)) {
                    echo '<tr><td colspan="4" class="text-center">No employee data found. Please check Master setup or Attendance records.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
    include_once('footer.php');
?>
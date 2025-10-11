<?php 
include_once('header.php');
include_once('navbar.php');
?>

<div class="container-fluid mt-3">
    <div class="row justify-content-between align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="display-5 fw-bold">Complete Employees Name List</h1>
            <!-- <p class="text-muted">Current month's salary shown below is editable directly via the 'EDIT' button.</p> -->
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#myModal">
                ADD EMPLOYEE NAME
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>EMPLOYEE NAME</th>
                    <th>BANK A/C NO.</th>
                    <th>BANK IFSC CODE</th>
                    <!-- Displaying the current month's salary -->
                    <th>DAILY SALARY (₹)</th> 
                    <th>ACTIVE</th>
                    <th>EDIT</th>
                    <th>DELETE</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    // $i=1; // Sr. No. is commented out, keeping the original structure
                    foreach($viewdata as $key)
                    {
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key -> employee_name); ?></td>
                        <td><?php echo htmlspecialchars($key -> bank_ac_number); ?></td>
                        <td><?php echo htmlspecialchars($key -> bank_ifsc_code); ?></td>
                        <!-- Displaying the current month's salary from the merged object property -->
                        <td><?php echo htmlspecialchars($key -> current_salary); ?></td>
                        <td>
                            <?php if ($key -> active == 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-employee-id="<?php echo $key->employee_id; ?>"
                                data-employee-name="<?php echo htmlspecialchars($key->employee_name); ?>"
                                data-bank_ac_number="<?php echo htmlspecialchars($key->bank_ac_number); ?>"
                                data-bank_ifsc_code="<?php echo htmlspecialchars($key->bank_ifsc_code); ?>"
                                data-salary="<?php echo $key->current_salary; ?>" 
                                data-active="<?php echo $key->active; ?>"
                            >
                                EDIT
                            </button>
                        </td>
                        <td>
                            <form method="post" onsubmit="return confirm('ARE YOU SURE YOU WANT TO DELETE THIS EMPLOYEE!');">
                                <input type="hidden" name="employee_id" value="<?php echo $key->employee_id; ?>">
                                <button type="submit" class="btn btn-outline-danger" name="del_employee">
                                    DELETE
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php
                    // $i++;
                    }
                ?>
            </tbody>
        </table>
    </div>

<p class="text-muted mt-3 mb-3 small">
<strong>Note:</strong> In the <b>ACTIVE</b> column, <b>1</b> means <b>working with us</b>, and <b>0</b> means <b>not with us</b> (inactive).
</p>

</div>


<!-- ADD EMPLOYEE Modal -->
<div class="modal fade" id="myModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Add Employee</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <form method="POST">
                    <div class="form-floating mb-3 mt-3">
                        <input type="text" class="form-control" id="name" placeholder="Enter Name" name="employee_name" required>
                        <label>Name</label>
                    </div>
                    <div class="form-floating mt-3 mb-3">
                        <input type="text" class="form-control" id="bank_ac_number" placeholder="Enter Bank Account Number" name="bank_ac_number">
                        <label>Bank Account Number</label>
                    </div>
                    <div class="form-floating mt-3 mb-3">
                        <input type="text" class="form-control" id="bank_ifsc_code" placeholder="Enter Bank IFSC Code" name="bank_ifsc_code">
                        <label>Bank IFSC Code</label>
                    </div>
                    <!-- Salary input for initial/current month salary -->
                    <div class="form-floating mt-3 mb-3">
                        <input type="number" step="0.01" class="form-control" id="salary" placeholder="Enter Daily Wages" name="salary" min="0" required>
                        <label>Daily Salary (₹)</label>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_employee">Submit</button>
                </form>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<!-- EDIT EMPLOYEE Modal -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Edit Employee Details</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="edit_employee_id" id="edit_employee_id">
                    <div class="mb-3 mt-3">
                        <label>Name:</label>
                        <input type="text" class="form-control" name="edit_employee_name" id="edit_employee_name" required>
                    </div>
                    <div class="mb-3">
                        <label>Bank Account Number:</label>
                        <input type="text" class="form-control" name="edit_bank_ac_number" id="edit_bank_ac_number">
                    </div>
                    <div class="mb-3">
                        <label>Bank IFSC Code:</label>
                        <input type="text" class="form-control" name="edit_bank_ifsc_code" id="edit_bank_ifsc_code">
                    </div>
                    <!-- Salary input for current month salary -->
                    <div class="mb-3">
                        <label>Daily Salary (₹):</label>
                        <input type="number" step="0.01" class="form-control" name="edit_salary" id="edit_salary" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label>Is Currently Working with us:</label>
                        <input type="checkbox" name="active" id="edit_active_checkbox" value="1">
                    </div>
                    <button type="submit" class="btn btn-primary" name="edit_employee">Submit</button>
                </form>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<script>
    var editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;

        var employee_id = button.getAttribute('data-employee-id');
        var employee_name = button.getAttribute('data-employee-name');
        var bank_ac_number = button.getAttribute('data-bank_ac_number');
        var bank_ifsc_code = button.getAttribute('data-bank_ifsc_code');
        // Retrieve the current month's salary
        var salary = button.getAttribute('data-salary'); 
        var active_status = button.getAttribute('data-active');

        document.getElementById('edit_employee_id').value = employee_id;
        document.getElementById('edit_employee_name').value = employee_name;
        document.getElementById('edit_bank_ac_number').value = bank_ac_number;
        document.getElementById('edit_bank_ifsc_code').value = bank_ifsc_code;
        // Populate the salary field
        document.getElementById('edit_salary').value = salary; 

        var activeCheckbox = document.getElementById('edit_active_checkbox');

        if (active_status == '1') {
            activeCheckbox.checked = true; 
        } else {
            activeCheckbox.checked = false;
        }
    });
</script>

<?php
include_once('footer.php');
?>

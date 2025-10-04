<?php 
include_once('header.php');
include_once('navbar.php');
?>

<div class="container-fluid mt-3">
    <div class="row justify-content-between align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="display-5 fw-bold">Complete Employees Name List</h1>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#myModal">
                ADD EMPLOYEE NAME
            </button>
        </div>
    </div>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>SR. NO.</th>
                <th>EMPLOYEE ID</th>
                <th>EMPLOYEE NAME</th>
                <th>BANK A/C NO.</th>
                <th>BANK IFSC CODE</th>
                <th>ACTIVE</th>
                <th>EDIT</th>
                <th>DELETE</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $i=1;
                foreach($viewdata as $key)
                {
            ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><?php echo $key -> employee_id; ?></td>
                    <td><?php echo $key -> employee_name; ?></td>
                    <td><?php echo $key -> bank_ac_number; ?></td>
                    <td><?php echo $key -> bank_ifsc_code; ?></td>
                    <td><?php echo $key -> active; ?></td>
                    <td>
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal"
                            data-employee-id="<?php echo $key->employee_id; ?>"
                            data-employee-name="<?php echo $key->employee_name; ?>"
                            data-bank_ac_number="<?php echo $key->bank_ac_number; ?>"
                            data-bank_ifsc_code="<?php echo $key->bank_ifsc_code; ?>"
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
                $i++;
                }
            ?>
        </tbody>
  </table>

<!-- <br> -->
<!-- <hr> -->
<p class="text-muted mt-3 mb-3 small">
<strong>Note:</strong> In the <b>ACTIVE</b> column, <b>1</b> means <b>working with us</b>, and <b>0</b> means <b>not with us</b> (inactive).
</p>
<!-- <div class="mt-3 pt-2 pb-1 border-bottom border-secondary text-muted small">
    **ACTIVE** Column: **1** = Working with us, **0** = Not with us (Inactive).
</div> -->

</div>



<!-- The Modal -->
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
                        <input type="text" class="form-control" id="bank_ac_number" placeholder="Enter Bank Account Number" name="bank_ac_number" required>
                        <label>Bank Account Number</label>
                    </div>
                    <div class="form-floating mt-3 mb-3">
                        <input type="text" class="form-control" id="bank_ifsc_code" placeholder="Enter Bank IFSC Code" name="bank_ifsc_code" required>
                        <label>Bank IFSC Code</label>
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

<!-- The Modal -->
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
                        <input type="text" class="form-control" name="edit_bank_ac_number" id="edit_bank_ac_number" required>
                    </div>
                    <div class="mb-3">
                        <label>Bank IFSC Code:</label>
                        <input type="text" class="form-control" name="edit_bank_ifsc_code" id="edit_bank_ifsc_code" required>
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
        var active_status = button.getAttribute('data-active');

        document.getElementById('edit_employee_id').value = employee_id;
        document.getElementById('edit_employee_name').value = employee_name;
        document.getElementById('edit_bank_ac_number').value = bank_ac_number;
        document.getElementById('edit_bank_ifsc_code').value = bank_ifsc_code;

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

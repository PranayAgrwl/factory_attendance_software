<?php

require_once ("app/models/Model.php");

// 1. Load the vendor folder
require __DIR__ . '/../../vendor/autoload.php';

// 2. Import the main classes we need from PHPSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class Controller
{
	private $model;
	public function __construct()
	{
		$this->model = new Model();
	}
	// Inside app/controllers/Controller.php

	public function login()
	{
		// session_start();
		// Check if the user is already logged in (simple "middleware")
		if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
			// Redirect to the home page or dashboard if already authenticated
			header('Location: index'); 
			exit();
		}

		$error_message = '';

		if (isset($_POST['submit_login'])) {
			$username = trim($_POST['username']);
			$password = $_POST['password'];
			$captcha  = trim($_POST['captcha']);
			$otp      = trim($_POST['otp']);


			// --- NEW: Read Lat/Lon from POST ---
			$latitude  = isset($_POST['lat']) ? (float)$_POST['lat'] : 0.00;
			$longitude = isset($_POST['lon']) ? (float)$_POST['lon'] : 0.00;



			// 1. Fetch user data based on username
			$user_data = $this->model->selectDataWithCondition('users', ['username' => $username]);

			if (empty($user_data)) {
				$error_message = 'Invalid credentials.';
			} else {
				$user = $user_data[0];

				// 2. Verify all three credentials
				// IMPORTANT: Replace 'password_verify' with plain comparison if you are NOT hashing passwords (NOT RECOMMENDED)
				// $password_match = password_verify($password, $user->password_hash);
				$password_match = ($password === $user->password);
				$captcha_match  = ($captcha === $user->static_captcha);
				$otp_match      = ($otp === $user->static_otp);

				if ($password_match && $captcha_match && $otp_match) {
					// 3. SUCCESS: Set session variables (the "middleware" key)
					// session_start();
					$_SESSION['logged_in'] = true;
					$_SESSION['user_id'] = $user->user_id;
					$_SESSION['username'] = $user->username;
					// $_SESSION['employee_name'] = $user->employee_name;

					$ip_address = $_SERVER['REMOTE_ADDR'];
                    
                    $history_data = [
                        'user_id'    => $user->user_id,
                        'login_time' => date('Y-m-d H:i:s'),
                        'ip_address' => $ip_address,

						// --- NEW: Include location in INSERT query ---
						'latitude'   => $latitude,
						'longitude'  => $longitude
						// ----------------------------------------------
                    ];

					$history_id = $this->model->insertData('user_history', $history_data);
					// $_SESSION['history_id'] is no longer needed since location is saved here.
					// You can remove it or keep it if other parts of the system rely on it.
					// For this change, we'll remove it:
					// $_SESSION['history_id'] = $history_id; // REMOVE

					// Redirect to the protected home page
					header('Location: index');
					exit();
				} else {
					$error_message = 'Invalid credentials or code.';
				}
			}
		}

		// Load the login view
		include ('app/views/login.php'); 
	}

	// -------------------------------------------------------------
	// You will also need a function to enforce the login ("middleware") 
	// at the start of every protected controller method (like index, home, etc.)

	private function enforceLogin() {
		if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
			// Redirect to login page if not logged in
			header('Location: login'); 
			exit();
		}
	}

	// Example usage in another controller method:
	public function home() {
		$this->enforceLogin(); // Run the middleware check
		// ... rest of your home page logic
	}


	public function index()
	{
		$this->enforceLogin(); // Run the middleware check
		include ('app/views/index.php');
		
	}
	public function error404()
	{
		$this->enforceLogin(); // Run the middleware check
		include ('app/views/error404.php');
	}
	public function master()
    {
        $this->enforceLogin(); // Run the middleware check
        $viewdata = $this -> model -> selectData ("employees_list");
        usort($viewdata, function($a, $b) 
        {
            return strcasecmp($a->employee_name, $b->employee_name); // A-Z
        });

        if(isset($_REQUEST['edit_employee']))
        {
            $employee_id = $_REQUEST['edit_employee_id'];
            $employee_name = $_REQUEST['edit_employee_name'];
            
            // --- Bank Account Number Edit Logic ---
            $bank_ac_number = empty($_REQUEST['edit_bank_ac_number']) ? '0' : $_REQUEST['edit_bank_ac_number'];
            // --- IFSC Code Edit Logic ---
            $bank_ifsc_code = empty($_REQUEST['edit_bank_ifsc_code']) ? '0' : $_REQUEST['edit_bank_ifsc_code'];
            
            // --- Salary Edit Logic ---
            // If the salary is empty (null or an empty string), set it to '0'.
            $salary = empty($_REQUEST['edit_salary']) ? '0' : $_REQUEST['edit_salary']; 
            // -------------------------

            $active_status = isset($_REQUEST['active']) ? $_REQUEST['active'] : 0;
            
            
            $edit_data=[
                "employee_name"  => $employee_name, 
                "bank_ac_number" => $bank_ac_number, 
                "bank_ifsc_code" => $bank_ifsc_code,
                "salary" => $salary,
                "active"         => $active_status // comment
            ];  
                
            // echo "<pre>";
            // echo $edit_data;
            // exit();  

            $result = $this -> model -> updateData ("employees_list", $edit_data, ['employee_id'=>$employee_id]);

            // echo "<h2>DEBUGGING UPDATE</h2>";
            // echo "<p>Employee ID: " . $employee_id . "</p>";
            // echo "<p>Active Status Submitted: " . $active_status . "</p>";
            // echo "<pre>Data to Update:\n";
            // print_r($edit_data);
            // exit;


            if(isset($result))
            {
                // echo "Update Success";
                header("Location: master");
                exit();
            }
            else
            {
                echo "Error Updating Database";
            }
        }

        if(isset($_REQUEST['add_employee']))
        {
            $employee_name=$_REQUEST['employee_name'];
            
            // --- Bank Account Number Add Logic ---
            $bank_ac_number = empty($_REQUEST['bank_ac_number']) ? '0' : $_REQUEST['bank_ac_number'];
            // --- IFSC Code Add Logic ---
            $bank_ifsc_code = empty($_REQUEST['bank_ifsc_code']) ? '0' : $_REQUEST['bank_ifsc_code'];
            
            // --- Salary Add Logic ---
            // If the salary is empty (null or an empty string), set it to '0'.
            $salary = empty($_REQUEST['salary']) ? '0' : $_REQUEST['salary'];
            // ------------------------
            
            $active_status = 1; 

            $data=[
                "employee_name"  => $employee_name, 
                "bank_ac_number" => $bank_ac_number, 
                "bank_ifsc_code" => $bank_ifsc_code,
                "salary" => $salary,
                "active"         => $active_status // comment
            ];

            $result = $this -> model -> insertData ("employees_list", $data);
            if(isset($result))
            {
                // echo "Data Inserted";
                header("Location: master");
                exit();
            }
            else
            {
                echo "Error Inserting Data";
            }
        }

        if(isset($_REQUEST['del_employee']))
        {
            $employeeid = $_REQUEST['employee_id'];
            // echo $employeeid;
            // exit();
            $result = $this -> model -> deleteData ("employees_list", ['employee_id' => $employeeid]);
            if(isset($result))
            {
                // echo "Data Deleted Successfuly";
                header("Location: master");
                exit();
            }
            else
            {
                echo "Error Deleting Data";
            }
        }

        include ('app/views/master.php');

    }
	
	public function attendance()
    {
        $this->enforceLogin(); // Run the middleware check
        $selected_date = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d');

		$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
		$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));
        
        // Initialize variables to empty arrays so the view doesn't throw errors if they're not set.
        $employee_names = [];
        $attendance_map = [];
        // NEW: Map to hold current salaries for snapping
        $employee_salary_map = [];


        // 1. Fetch all employees (required for getting current salaries and display)
        $employee_names = $this->model->selectData('employees_list');
        
        usort($employee_names, function($a, $b) 
        {
            return strcasecmp($a->employee_name, $b->employee_name); 
        });

        // Populate the salary map for easy lookup when saving data
        foreach ($employee_names as $emp) {
            // Use null coalescing ?? to safely get salary, defaulting to 0.00 if unset/null
            $employee_salary_map[$emp->employee_id] = (float)($emp->salary ?? 0.00); 
        }

        // 2. Handle Data Submission (Saving Attendance)
        if (isset($_POST['save_attendance']) && isset($_POST['entries'])) {
            $data_to_save = $_POST['entries'];

            foreach ($data_to_save as $employee_id => $entry) {
                
                $employee_id = (int)$employee_id;
                
                // CRUCIAL: Get the CURRENT salary from the map for the snapshot
                $current_salary = $employee_salary_map[$employee_id] ?? 0.00;
                
                // NEW: Function to safely convert input to float, treating empty string as 0.00
                $safe_float = function($value) {
                    // Check if the value is set, not empty, and not just whitespace
                    if (isset($value) && is_numeric($value) && $value !== '') {
                        return (float)$value;
                    }
                    return 0.00;
                };

                // Prepare attendance data fields that are common to both INSERT and UPDATE
                $attendance_fields = [
                    // Apply the new safe_float function to automatically convert empty/null to 0.00
                    'daily_attendance' => $safe_float($entry['attendance'] ?? null),
                    'extra_work' => $safe_float($entry['extra_work'] ?? null),
                    'advance_taken' => $safe_float($entry['advance_taken'] ?? null),
                ];
                
                // Check for existing record 
                $existing_records = $this->model->selectDataWithCondition(
                    'daily_attendance_report',
                    ['entry_date' => $selected_date, 'employee_id' => $employee_id]
                );

                if (!empty($existing_records)) {
                    // UPDATE existing record
                    // We DO NOT update 'salary_snapshot' during an edit, as its purpose is historical recording.
                    $this->model->updateData(
                        'daily_attendance_report',
                        $attendance_fields,
                        ['entry_id' => $existing_records[0]->entry_id]
                    );
                    
                } else {
                    // INSERT new record
                    // Merge fields with the snapshot data for insertion
                    $insert_data = array_merge($attendance_fields, [
                        'entry_date' => $selected_date,
                        'employee_id' => $employee_id,
                        'salary_snapshot' => $current_salary, // NEW: Snapshot the current salary
                    ]);
                    
                    $this->model->insertData('daily_attendance_report', $insert_data);
                }
            }

            // Redirect back to the same page with the selected date and success status
            header("Location: attendance?date=" . $selected_date . "&status=saved");
            exit();
        }

        // 3. Fetch Existing Attendance Data for Display
        
        // Fetch existing attendance data for the selected date
        $existing_attendance = $this->model->selectDataWithCondition(
            'daily_attendance_report',
            ['entry_date' => $selected_date]
        );
        
        // Convert attendance records into an associative array keyed by employee_id for easy lookup in the view
        $attendance_map = [];
        foreach ($existing_attendance as $record) {
            $attendance_map[$record->employee_id] = $record;
        }

        // Pass all necessary variables to the view
        include ('app/views/attendance.php');
    }


    public function monthly_report()
    {
        $this->enforceLogin(); 
        // Run the middleware check
        
        // 1. Determine the selected month and year
        // Default to the current month/year if nothing is selected
        $selected_year_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date('Y-m'); 
        
        // Split the 'YYYY-MM' string into separate year and month variables
        list($year, $month) = explode('-', $selected_year_month);
        
        // Calculate the total number of days in the selected month (e.g., 30 or 31)
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // --- NEW: Calculate previous and next month links for navigation ---
        $prev_month_ts = strtotime($selected_year_month . '-01 -1 month');
        $next_month_ts = strtotime($selected_year_month . '-01 +1 month');
        
        $prev_month = date('Y-m', $prev_month_ts);
        $next_month = date('Y-m', $next_month_ts);
        // ------------------------------------------------------------------

        // 2. Fetch data
        
        // Fetch ALL employees (active or not) to include historical data in the report
        $employee_names = $this->model->selectData('employees_list'); 

        // Sort employees by name (A-Z)
        usort($employee_names, function($a, $b) 
        {
            return strcasecmp($a->employee_name, $b->employee_name); 
        });

        // Fetch ALL attendance records for the selected month/year
        // Note: This assumes your Model can handle a LIKE query or a range query
        $attendance_records = $this->model->selectDataWithCondition(
            'daily_attendance_report',
            ['entry_date LIKE' => "{$selected_year_month}%"] // e.g., '2025-10%'
        );
        
        // 3. Process data into a report map
        
        /* * The report map will store summarized data:
        * [employee_id] => [
        * 'total_shifts' => 22,
        * 'total_extra' => 500,
        * 'total_advance' => 1000,
        * 'dates' => ['2025-10-01' => {record}, '2025-10-02' => {record}, ...]
        * ]
        */
        $monthly_report_map = [];

        foreach ($attendance_records as $record) {
            $employee_id = $record->employee_id;

            // Initialize map entry if it doesn't exist
            if (!isset($monthly_report_map[$employee_id])) {
                $monthly_report_map[$employee_id] = [
                    'total_shifts' => 0,
                    'total_extra' => 0.00,
                    'total_advance' => 0.00,
                    'dates' => []
                ];
            }

            // Aggregate totals
            $monthly_report_map[$employee_id]['total_shifts'] += $record->daily_attendance;
            $monthly_report_map[$employee_id]['total_extra'] += $record->extra_work;
            $monthly_report_map[$employee_id]['total_advance'] += $record->advance_taken;
            
            // Store the daily record by date for detailed view
            $monthly_report_map[$employee_id]['dates'][$record->entry_date] = $record;
        }

        // 4. Load the view, passing all data
        include ('app/views/monthly_report.php');
    }

	public function logout()
	{
		// Start the session if not already started (just in case)
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		
		// Unset all session variables
		$_SESSION = array();

		// Destroy the session (deletes the session file on the server)
		session_destroy();

		// Clear the session cookie if possible (makes sure the browser forgets the old session ID)
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
		
		// Redirect the user to the login page
		header('Location: login'); 
		exit();
	}

	public function salary_report()
    {
        $this->enforceLogin(); 
        
        $selected_year_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date('Y-m'); 
        
        // --- NEW: Calculate previous and next month links for navigation ---
        $prev_month_ts = strtotime($selected_year_month . '-01 -1 month');
        $next_month_ts = strtotime($selected_year_month . '-01 +1 month');
        
        $prev_month = date('Y-m', $prev_month_ts);
        $next_month = date('Y-m', $next_month_ts);
        // ------------------------------------------------------------------

        $employee_list = $this->model->selectData('employees_list'); 
        $attendance_records = $this->model->selectDataWithCondition(
            'daily_attendance_report',
            ['entry_date LIKE' => "{$selected_year_month}%"] 
        );
        
        $monthly_report_map = [];

        foreach ($attendance_records as $record) {
            $employee_id = $record->employee_id;

            if (!isset($monthly_report_map[$employee_id])) {
                $monthly_report_map[$employee_id] = [
                    'total_shifts' => 0,
                    'total_extra' => 0.00,
                    'total_advance' => 0.00,
                    'total_earnings' => 0.00
                ];
            }

            $monthly_report_map[$employee_id]['total_shifts'] += $record->daily_attendance;
            $monthly_report_map[$employee_id]['total_extra'] += $record->extra_work;
            $monthly_report_map[$employee_id]['total_advance'] += $record->advance_taken;
            
            $daily_salary = (float)($record->salary_snapshot ?? 0.00);
            $monthly_report_map[$employee_id]['total_earnings'] += ((float)$record->daily_attendance * $daily_salary);
        }

        // 4. Combine employee details with financial totals and CALCULATE GRAND TOTAL ACROSS ALL EMPLOYEES
        $salary_details = [];
        $total_payable_grand_total = 0.00; // This will now sum positives AND negatives
        
        foreach ($employee_list as $employee) {
            $employee_id = $employee->employee_id; 
            $report = $monthly_report_map[$employee_id] ?? null;
            
            // Only proceed if the employee is active OR if they have records for the month
            if ($report === null && $employee->active != 1) {
                continue;
            }

            $total_earnings = $report['total_earnings'] ?? 0.00;
            $total_extra = $report['total_extra'] ?? 0.00;
            $total_advance = $report['total_advance'] ?? 0.00;
            
            // Final Calculation
            $net_due = $total_earnings + $total_extra - $total_advance;

            // *** CHANGE 2: CONDITIONALLY ADD to the Grand Total ***
            // Now, only positive net_due values are included in the grand total sum.
            if ($net_due > 0.00) {
                 $total_payable_grand_total += $net_due;
            }
            
            $salary_details[] = (object)[
                'employee_name' => $employee->employee_name,
                'bank_ac_number' => $employee->bank_ac_number,
                'bank_ifsc_code' => $employee->bank_ifsc_code,
                'net_due' => $net_due,
            ];
        }
        
        // Sort the final list by name
        usort($salary_details, function($a, $b) {
            return strcasecmp($a->employee_name, $b->employee_name); 
        });

        $viewdata = [
            'salary_details' => $salary_details,
            'selected_year_month' => $selected_year_month,
            'total_payable_grand_total' => $total_payable_grand_total,
            // --- NEW: Pass calculated dates to the view ---
            'prev_month' => $prev_month,
            'next_month' => $next_month
            // ----------------------------------------------
        ];

        include ('app/views/salary_report.php');
    }


    public function export_salary_report()
    {
        $this->enforceLogin(); 
        
        $selected_year_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date('Y-m'); 
        
        // --- DATA COLLECTION (Skipping logic block for brevity, assumed unchanged) ---
        $employee_list = $this->model->selectData('employees_list'); 
        $attendance_records = $this->model->selectDataWithCondition(
            'daily_attendance_report',
            ['entry_date LIKE' => "{$selected_year_month}%"] 
        );
        
        $monthly_report_map = [];
        // ... (aggregation logic remains the same) ...
        foreach ($attendance_records as $record) {
            $employee_id = $record->employee_id;
            if (!isset($monthly_report_map[$employee_id])) {
                $monthly_report_map[$employee_id] = [
                    'total_shifts' => 0,
                    'total_extra' => 0.00,
                    'total_advance' => 0.00,
                    'total_earnings' => 0.00
                ];
            }
            $monthly_report_map[$employee_id]['total_shifts'] += $record->daily_attendance;
            $monthly_report_map[$employee_id]['total_extra'] += $record->extra_work;
            $monthly_report_map[$employee_id]['total_advance'] += $record->advance_taken;
            
            $daily_salary = (float)($record->salary_snapshot ?? 0.00);
            $monthly_report_map[$employee_id]['total_earnings'] += ((float)$record->daily_attendance * $daily_salary);
        }
        
        // --- FINAL DATA PREPARATION (Fixing Symbols, Filtering Positive Net Due) ---
        $export_data = [];
        
        // *** REMOVED: Zero-Width Space is no longer defined or used. ***
        
        foreach ($employee_list as $employee) {
            $employee_id = $employee->employee_id;
            $report = $monthly_report_map[$employee_id] ?? null;
            
            if ($report === null && $employee->active != 1) {
                continue;
            }

            $total_earnings = $report['total_earnings'] ?? 0.00;
            $total_extra = $report['total_extra'] ?? 0.00;
            $total_advance = $report['total_advance'] ?? 0.00;
            
            $net_due = $total_earnings + $total_extra - $total_advance;

            // *** FIX 1: Only include records where net_due is greater than zero ***
            if ($net_due <= 0.00) {
                continue; 
            }

            // Prepare the array for CSV output
            $export_data[] = [
                // Capitalization
                'Employee Name' => strtoupper($employee->employee_name),
                
                // *** FIX 2: Removed $zws prefix to show raw value ***
                'Bank AC Number' => strtoupper((string)$employee->bank_ac_number),
                
                // *** FIX 2: Removed $zws prefix to show raw value ***
                'IFSC Code' => strtoupper($employee->bank_ifsc_code),
                
                'Net Due (INR)' => number_format($net_due, 2, '.', ''),
            ];
        }

        // --- CSV GENERATION AND DOWNLOAD (No changes needed here) ---
        
        $filename = "salary_report_{$selected_year_month}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($export_data)) {
            fputcsv($output, array_keys($export_data[0]));
        }
        
        foreach ($export_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }

	public function export_salary_report_xlsx()
    {
        $this->enforceLogin(); 
        
        $selected_year_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date('Y-m'); 
        
        // --- 1. DATA COLLECTION (Same logic as CSV export) ---
        $employee_list = $this->model->selectData('employees_list'); 
        $attendance_records = $this->model->selectDataWithCondition(
            'daily_attendance_report',
            ['entry_date LIKE' => "{$selected_year_month}%"] 
        );
        
        $monthly_report_map = [];
        foreach ($attendance_records as $record) {
            $employee_id = $record->employee_id;
            if (!isset($monthly_report_map[$employee_id])) {
                $monthly_report_map[$employee_id] = [
                    'total_shifts' => 0,
                    'total_extra' => 0.00,
                    'total_advance' => 0.00,
                    'total_earnings' => 0.00
                ];
            }
            $monthly_report_map[$employee_id]['total_shifts'] += $record->daily_attendance;
            $monthly_report_map[$employee_id]['total_extra'] += $record->extra_work;
            $monthly_report_map[$employee_id]['total_advance'] += $record->advance_taken;
            
            $daily_salary = (float)($record->salary_snapshot ?? 0.00);
            $monthly_report_map[$employee_id]['total_earnings'] += ((float)$record->daily_attendance * $daily_salary);
        }
        
        // --- 2. FINAL DATA PREPARATION ---
        $export_data = [];
        $grand_total = 0.00; // Total of only POSITIVE net_due values

        // *** Setting up the custom headers ***
        $export_data[] = [
            'Employee Name',
            'Bank Account Number',
            'Bank IFSC Code',
            'Net Due' // Removed (INR)
        ];
        
        foreach ($employee_list as $employee) {
            $employee_id = $employee->employee_id;
            $report = $monthly_report_map[$employee_id] ?? null;
            
            if ($report === null && $employee->active != 1) {
                continue;
            }

            $total_earnings = $report['total_earnings'] ?? 0.00;
            $total_extra = $report['total_extra'] ?? 0.00;
            $total_advance = $report['total_advance'] ?? 0.00;
            
            $net_due = $total_earnings + $total_extra - $total_advance;

            // Filter 1: Only include records where net_due is greater than zero
            if ($net_due <= 0.00) {
                continue; 
            }

            // Calculate the total of payable salaries
            $grand_total += $net_due;

            // Prepare the array for Excel processing.
            $export_data[] = [
                'Employee Name' => strtoupper($employee->employee_name),
                'Bank Account Number' => (string)$employee->bank_ac_number, 
                'Bank IFSC Code' => strtoupper((string)$employee->bank_ifsc_code),
                'Net Due' => $net_due, // Use raw numeric value
            ];
        }

        // *** FIX 3: Add the TOTAL row at the end ***
        $export_data[] = [
            'Employee Name' => 'TOTAL',
            'Bank Account Number' => '',
            'Bank IFSC Code' => '',
            'Net Due' => $grand_total,
        ];


        // --- 3. XLSX GENERATION AND DOWNLOAD SETUP (Simplified Formatting) ---
        
        $filename = "salary_report_{$selected_year_month}.xlsx";
        
        // Set headers for XLSX download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0'); 

        // 1. Initialize Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 2. Populate data starting from A1 (includes headers)
        $sheet->fromArray($export_data, NULL, 'A1');

        // 3. Apply necessary formatting without excessive styling
        
        // Get the last row index for the Total row
        $last_data_row = count($export_data);

        // Auto size columns for readability (only essential styling)
        foreach (range('A', 'D') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // IMPORTANT: Format Column B (Bank AC Number) and C (IFSC) as TEXT to prevent data loss.
        // This is necessary even without custom styling.
        $sheet->getStyle('B2:C' . $last_data_row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        
        // Format Column D (Net Due) as a simple number with 2 decimals (no currency symbol).
        // Apply to the entire column for consistency, including the total row.
        $sheet->getStyle('D:D')->getNumberFormat()->setFormatCode('0.00'); 
        

        // 4. Create the writer and stream the file
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit();
    }

}
?>
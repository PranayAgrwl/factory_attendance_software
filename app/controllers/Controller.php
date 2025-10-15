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

	public function login()
	{
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
				$password_match = ($password === $user->password);
				$captcha_match  = ($captcha === $user->static_captcha);
				$otp_match      = ($otp === $user->static_otp);

                // NEW: Get user authority
                $user_authority = $user->authority ?? 'regular';

				if ($password_match && $captcha_match && $otp_match) {
					// 3. SUCCESS: Set session variables (the "middleware" key)
					// session_start();
					$_SESSION['logged_in'] = true;
					$_SESSION['user_id'] = $user->user_id;
					$_SESSION['username'] = $user->username;
                    $_SESSION['authority'] = $user_authority; // Store user authority in session
					// $_SESSION['employee_name'] = $user->employee_name;

					$ip_address = $_SERVER['REMOTE_ADDR'];
                    
                    $history_data = [
                        'user_id'    => $user->user_id,
                        'login_time' => date('Y-m-d H:i:s'),
                        'ip_address' => $ip_address,
						'latitude'   => $latitude,
						'longitude'  => $longitude
                    ];

					$history_id = $this->model->insertData('user_history', $history_data);
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


	private function enforceLogin() {
		if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
			// Redirect to login page if not logged in
			header('Location: login'); 
			exit();
		}
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
        $current_user_id = $_SESSION['user_id'];
        
        // --- 1. Fetch all employees ---
        $viewdata = $this -> model -> selectData ("employees_list");
        usort($viewdata, function($a, $b) 
        {
            return strcasecmp($a->employee_name, $b->employee_name); // A-Z
        });

        // --- 2. Fetch Current Month Salary Data (YYYY-MM-01 format) ---
        $current_month_db_format = date('Y-m-01');
        $current_month_salaries = $this->model->selectDataWithCondition(
            'monthwise_salary',
            ['salary_month' => $current_month_db_format]
        );

        // Map salary records by employee_id
        $salary_map = [];
        foreach ($current_month_salaries as $record) {
            // Store formatted salary for easier display in the view
            $salary_map[$record->employee_id] = number_format($record->salary, 2, '.', '');
        }

        // --- 3. Merge Current Salary into Employee List ($viewdata) ---
        foreach ($viewdata as $key) {
            // Add current_salary property to each employee object
            $key->current_salary = $salary_map[$key->employee_id] ?? '0.00';
        }
        
        // -------------------------------------------------------------------
        // --- CRUD LOGIC START ---
        // -------------------------------------------------------------------
        
        // --- ADD EMPLOYEE AND INITIAL SALARY ---
        if(isset($_REQUEST['add_employee']))
        {
            $employee_name=$_REQUEST['employee_name'];
            $bank_ac_number = empty($_REQUEST['bank_ac_number']) ? '0' : $_REQUEST['bank_ac_number'];
            $bank_ifsc_code = empty($_REQUEST['bank_ifsc_code']) ? '0' : $_REQUEST['bank_ifsc_code'];
            
            // Capture and sanitize the initial salary
            $initial_salary = (float)(filter_var($_REQUEST['salary'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
            $active_status = 1; 

            $data=[
                "employee_name"  => $employee_name, 
                "bank_ac_number" => $bank_ac_number, 
                "bank_ifsc_code" => $bank_ifsc_code,
                "active"         => $active_status,
                "created_by_user_id" => $current_user_id 
            ];

            $this->model->beginTransaction();
            
            // 1. Insert the new employee details.
            $employee_insert_success = $this->model->insertData("employees_list", $data);

            $new_employee_id = false;
            
            // 2. If the employee insertion was successful, retrieve the last inserted ID.
            if ($employee_insert_success !== false) {
                
                // Check if the insertData method efficiently returned the ID itself
                if (is_numeric($employee_insert_success) && $employee_insert_success > 0) {
                    $new_employee_id = $employee_insert_success;
                } else {
                    // *** FIX: Perform SELECT query to find the newly generated ID (as requested) ***
                    
                    // Search for the employee we just added using unique attributes (name and creator).
                    $latest_employees = $this->model->selectDataWithCondition(
                        "employees_list",
                        [
                            "employee_name"  => $employee_name, 
                            "bank_ac_number" => $bank_ac_number,
                            // bank_ifsc_code is also used for a more specific match
                            "created_by_user_id" => $current_user_id 
                        ]
                    );

                    // Find the record with the largest ID among the matches (which should be the one just inserted).
                    $max_id = 0;
                    foreach ($latest_employees as $employee) {
                        if ($employee->employee_id > $max_id) {
                            $max_id = $employee->employee_id;
                        }
                    }

                    if ($max_id > 0) {
                        $new_employee_id = $max_id;
                    } else {
                        // We couldn't find the record after inserting, indicating a failure
                        $new_employee_id = 0; 
                    }
                    // ---------------------------------------------------------------------------------
                }
            }
            
            // 3. Proceed only if a valid, numeric employee ID was obtained
            if ($new_employee_id && $new_employee_id > 0) {
                
                // Insert initial salary into monthwise_salary table for the current month
                if ($initial_salary > 0) {
                    $salary_data = [
                        // We now use the ID guaranteed to be from the newly inserted record
                        'employee_id' => $new_employee_id, 
                        'salary' => $initial_salary,
                        'salary_month' => date('Y-m-01') // Current month
                    ];
                    $salary_result = $this->model->insertData('monthwise_salary', $salary_data);
                    
                    if ($salary_result === false) {
                        $this->model->rollBack();
                        echo "Error Inserting Salary Data";
                        exit();
                    }
                }
                
                $this->model->commit();
                header("Location: master");
                exit();
            } else {
                $this->model->rollBack();
                echo "Error Inserting Employee Data (Could not retrieve new employee ID)";
                exit();
            }
        }

        // --- EDIT EMPLOYEE AND CURRENT MONTH SALARY ---
        if(isset($_REQUEST['edit_employee']))
        {
            $employee_id = $_REQUEST['edit_employee_id'];
            $employee_name = $_REQUEST['edit_employee_name'];
            $bank_ac_number = empty($_REQUEST['edit_bank_ac_number']) ? '0' : $_REQUEST['edit_bank_ac_number'];
            $bank_ifsc_code = empty($_REQUEST['edit_bank_ifsc_code']) ? '0' : $_REQUEST['edit_bank_ifsc_code'];
            
            // Capture and sanitize the edited salary
            $edited_salary = (float)(filter_var($_REQUEST['edit_salary'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
            $active_status = isset($_REQUEST['active']) ? $_REQUEST['active'] : 0;
            
            $edit_data=[
                "employee_name"  => $employee_name, 
                "bank_ac_number" => $bank_ac_number, 
                "bank_ifsc_code" => $bank_ifsc_code,
                "active"         => $active_status,
                "created_by_user_id" => $current_user_id 
            ];
            
            $current_month = date('Y-m-01');
            
            $this->model->beginTransaction();

            // 1. Update Employee List details
            $employee_update_result = $this -> model -> updateData ("employees_list", $edit_data, ['employee_id'=>$employee_id]);

            if ($employee_update_result !== false) {
                // 2. Update/Insert Salary for current month
                if ($edited_salary >= 0) {
                    
                    $existing_salary_record = $this->model->selectOne(
                        'monthwise_salary',
                        ['employee_id' => $employee_id, 'salary_month' => $current_month]
                    );

                    $salary_data = [
                        'employee_id' => $employee_id,
                        'salary' => $edited_salary,
                        'salary_month' => $current_month
                    ];

                    if ($existing_salary_record) {
                        // Update existing salary record
                        $salary_result = $this->model->updateData(
                            'monthwise_salary',
                            ['salary' => $edited_salary],
                            ['id' => $existing_salary_record->id]
                        );
                    } else if ($edited_salary > 0) {
                        // Insert new salary record (only if > 0)
                         $salary_result = $this->model->insertData('monthwise_salary', $salary_data);
                    } else {
                        // Salary is 0, and no record exists, so transaction succeeds silently.
                        $salary_result = true; 
                    }
                    
                    if ($salary_result === false) {
                        $this->model->rollBack();
                        echo "Error Updating/Inserting Salary Data";
                        exit();
                    }
                }
                
                $this->model->commit();
                header("Location: master");
                exit();
            } else {
                $this->model->rollBack();
                echo "Error Updating Employee Database";
                exit();
            }
        }

        // --- DELETE EMPLOYEE ---
        if(isset($_REQUEST['del_employee']))
        {
            $employeeid = $_REQUEST['employee_id'];
            // Note: In a production app, you might want to also delete related salary records 
            // or better yet, cascade the delete via foreign keys, but for simple logic:
            $result = $this -> model -> deleteData ("employees_list", ['employee_id' => $employeeid]);
            if(isset($result))
            {
                header("Location: master");
                exit();
            }
            else
            {
                echo "Error Deleting Data";
            }
        }

        // -------------------------------------------------------------------
        // --- LOAD VIEW ---
        // -------------------------------------------------------------------
        
        include ('app/views/master.php');

    }

	public function attendance()
    {
        $this->enforceLogin(); // Run the middleware check
        $current_user_id = $_SESSION['user_id'];
        $selected_date = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d');

        $prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
        $next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));
        
        // Initialize variables to empty arrays so the view doesn't throw errors if they're not set.
        $employee_names = [];
        $attendance_map = [];


        // 1. Fetch all active employees, sorted by name.
        $employee_names = $this->model->selectData('employees_list');
        
        usort($employee_names, function($a, $b) 
        {
            return strcasecmp($a->employee_name, $b->employee_name); 
        });

        // 2. Handle Data Submission (Saving Attendance)
        if (isset($_POST['save_attendance']) && isset($_POST['entries'])) {
            $data_to_save = $_POST['entries'];

            // Function to safely convert input to float, treating empty string as 0.00
            $safe_float = function($value) {
                // Check if the value is set, not empty, and not just whitespace
                if (isset($value) && is_numeric($value) && $value !== '') {
                    return (float)$value;
                }
                return 0.00;
            };

            foreach ($data_to_save as $employee_id => $entry) {
                
                $employee_id = (int)$employee_id;
               
                // Prepare attendance data fields that are common to both INSERT and UPDATE
                $attendance_fields = [
                    'daily_attendance' => $safe_float($entry['attendance'] ?? null),
                    'extra_work' => $safe_float($entry['extra_work'] ?? null),
                    'advance_taken' => $safe_float($entry['advance_taken'] ?? null),
                    'created_by_user_id' => $current_user_id 
                ];
                
                // Check for existing record 
                $existing_records = $this->model->selectDataWithCondition(
                    'daily_attendance_report',
                    ['entry_date' => $selected_date, 'employee_id' => $employee_id]
                );

                if (!empty($existing_records)) {
                    // UPDATE existing record
                    $this->model->updateData(
                        'daily_attendance_report',
                        $attendance_fields,
                        ['entry_id' => $existing_records[0]->entry_id]
                    );
                    
                } else {
                    // INSERT new record
                    $insert_data = array_merge($attendance_fields, [
                        'entry_date' => $selected_date,
                        'employee_id' => $employee_id,
                        // Removed 'salary_snapshot' as it is not required.
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
        
        // 1. Determine the selected month and year
        // Default to the current month/year if nothing is selected
        $selected_year_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date('Y-m'); 
        
        // Split the 'YYYY-MM' string into separate year and month variables
        list($year, $month) = explode('-', $selected_year_month);
        
        // Calculate the total number of days in the selected month (e.g., 30 or 31)
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // Determine the database key for the salary table (YYYY-MM-01)
        $salary_db_month = $selected_year_month . '-01'; 

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

        // --- NEW: Fetch Monthly Salary Data (Daily Wage) for the selected month ---
        $monthly_salaries = $this->model->selectDataWithCondition(
            'monthwise_salary',
            ['salary_month' => $salary_db_month]
        );

        $monthly_salary_map = [];
        foreach ($monthly_salaries as $record) {
            // Map the daily wage by employee ID. Salary is assumed to be the daily wage for the whole month.
            $monthly_salary_map[$record->employee_id] = (float)($record->salary ?? 0.00); 
        }
        // -------------------------------------------------------------------------
        
        // Fetch ALL attendance records for the selected month/year
        // Note: This assumes your Model can handle a LIKE query or a range query
        $attendance_records = $this->model->selectDataWithCondition(
            'daily_attendance_report',
            ['entry_date LIKE' => "{$selected_year_month}%"] // e.g., '2025-10%'
        );
        
        // 3. Process data into a report map
        
        /* * The report map will store summarized data:
        * [employee_id] => [
        * 'total_shifts' => 0,
        * 'total_extra' => 0.00,
        * 'total_advance' => 0.00,
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

        // 4. Filter the Employee List based on Status and Monthly Attendance
        /* * The core requirement is applied here: An employee is included if:
         * 1. They are currently active, OR
         * 2. They have at least one attendance entry in the $monthly_report_map for the selected month (even if they are inactive).
         * * ASSUMPTION: The employee object has an 'employee_status' property, 
         * and the value 'Active' (case-insensitive) denotes an active employee.
         */
        $employee_names = array_filter($employee_names, function($employee) use ($monthly_report_map) {
            // Check 1: Do they have attendance records this month?
            $has_attendance = isset($monthly_report_map[$employee->employee_id]);

            // Check 2: Are they currently marked as active?
            $is_active = (isset($employee->employee_status) && strtolower($employee->employee_status) === 'active');

            // Keep the employee if they are active OR if they have attendance data this month
            return $is_active || $has_attendance;
        });

        // 5. Load the view, passing all data
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
        
        // Calculate previous and next month links for navigation
        $prev_month_ts = strtotime($selected_year_month . '-01 -1 month');
        $next_month_ts = strtotime($selected_year_month . '-01 +1 month');
        
        $prev_month = date('Y-m', $prev_month_ts);
        $next_month = date('Y-m', $next_month_ts);
        
        // --- START CORRECTED SALARY LOGIC ---
        // Note: $days_in_month is no longer needed but kept for context if other logic depends on it.
        // $days_in_month = (int)date('t', strtotime($selected_year_month . '-01'));

        // 1. Fetch the Daily Wage records for the selected month
        $selected_month_db_format = $selected_year_month . '-01'; // Converts 'YYYY-MM' to 'YYYY-MM-01'
        $monthwise_salaries = $this->model->selectDataWithCondition(
            'monthwise_salary',
            ['salary_month' => $selected_month_db_format] // Uses the correct column name and format
        );

        // 2. Create a map of employee_id => daily_wage
        $daily_wage_map = []; // Renamed for clarity, since 'salary' field holds the daily wage
        foreach ($monthwise_salaries as $salary_record) {
            $daily_wage_map[$salary_record->employee_id] = (float)($salary_record->salary ?? 0.00);
        }
        // --- END CORRECTED SALARY LOGIC ---

        $employee_list = $this->model->selectData('employees_list'); 
        $attendance_records = $this->model->selectDataWithCondition(
            'daily_attendance_report',
            ['entry_date LIKE' => "{$selected_year_month}%"] 
        );
        
        $monthly_report_map = [];

        // 1. Aggregate attendance, extra work, and advances per employee for the month
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
            
            // --- UPDATED LOGIC: Use daily wage directly for earnings calculation ---
            $daily_wage = $daily_wage_map[$employee_id] ?? 0.00;
            $daily_salary_factor = $daily_wage; // The daily wage is the factor. No division by days_in_month.
            
            // Earnings = Shifts * Daily_Wage
            $monthly_report_map[$employee_id]['total_earnings'] += ((float)$record->daily_attendance * $daily_salary_factor);
            // --- END UPDATED LOGIC ---
        }

        // 2. Combine data, calculate final due, and calculate Grand Total
        $salary_details = [];
        $total_payable_grand_total = 0.00; // Sum of only positive net_due values
        
        foreach ($employee_list as $employee) {
            $employee_id = $employee->employee_id; 
            $report = $monthly_report_map[$employee_id] ?? null;
            
            // Filter: Only include employee if they are Active OR have an attendance entry this month
            if ($report === null && $employee->active != 1) {
                continue;
            }

            $total_earnings = $report['total_earnings'] ?? 0.00;
            $total_extra = $report['total_extra'] ?? 0.00;
            $total_advance = $report['total_advance'] ?? 0.00;
            
            // Final Calculation: Earnings + Extra - Advance
            $net_due = $total_earnings + $total_extra - $total_advance;

            // Only sum positive net_due values for the Payout Report total
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
        
        // Sort the final list by employee name
        usort($salary_details, function($a, $b) {
            return strcasecmp($a->employee_name, $b->employee_name); 
        });

        $viewdata = [
            'salary_details' => $salary_details,
            'selected_year_month' => $selected_year_month,
            'total_payable_grand_total' => $total_payable_grand_total,
            'prev_month' => $prev_month,
            'next_month' => $next_month
        ];

        include ('app/views/salary_report.php'); 
    }

    public function export_salary_report()
    {
        $this->enforceLogin(); 
        
        $selected_year_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date('Y-m'); 
        
        // --- START CORRECTED SALARY LOGIC ---
        // 1. Fetch the Daily Wage records for the selected month
        $selected_month_db_format = $selected_year_month . '-01'; // Converts 'YYYY-MM' to 'YYYY-MM-01'
        $monthwise_salaries = $this->model->selectDataWithCondition(
            'monthwise_salary',
            ['salary_month' => $selected_month_db_format] // Uses the correct column name and format
        );

        // 2. Create a map of employee_id => daily_wage
        $daily_wage_map = []; // Renamed for clarity
        foreach ($monthwise_salaries as $salary_record) {
            $daily_wage_map[$salary_record->employee_id] = (float)($salary_record->salary ?? 0.00);
        }
        // --- END CORRECTED SALARY LOGIC ---

        // --- Data Collection ---
        $employee_list = $this->model->selectData('employees_list'); 
        $attendance_records = $this->model->selectDataWithCondition(
            'daily_attendance_report',
            ['entry_date LIKE' => "{$selected_year_month}%"] 
        );
        
        $monthly_report_map = [];
        
        // Aggregation logic
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
            
            // --- UPDATED LOGIC: Use daily wage directly ---
            $daily_wage = $daily_wage_map[$employee_id] ?? 0.00;
            $daily_salary_factor = $daily_wage; // No division
            
            $monthly_report_map[$employee_id]['total_earnings'] += ((float)$record->daily_attendance * $daily_salary_factor);
            // --- END UPDATED LOGIC ---
        }
        
        // --- Final Data Preparation (Filtering for Payout Report: net_due > 0) ---
        $export_data = [];
        
        foreach ($employee_list as $employee) {
            $employee_id = $employee->employee_id;
            $report = $monthly_report_map[$employee_id] ?? null;
            
            // Filter 1: Only include employee if they are Active OR have an entry this month
            if ($report === null && $employee->active != 1) {
                continue;
            }

            $total_earnings = $report['total_earnings'] ?? 0.00;
            $total_extra = $report['total_extra'] ?? 0.00;
            $total_advance = $report['total_advance'] ?? 0.00;
            
            $net_due = $total_earnings + $total_extra - $total_advance;

            // Filter 2: Only include records where net_due is greater than zero
            if ($net_due <= 0.00) {
                continue; 
            }

            // Prepare the array for CSV output
            $export_data[] = [
                'Employee Name' => strtoupper($employee->employee_name),
                'Bank AC Number' => strtoupper((string)$employee->bank_ac_number),
                'IFSC Code' => strtoupper($employee->bank_ifsc_code),
                // Format net_due as a string with 2 decimals
                'Net Due (INR)' => number_format($net_due, 2, '.', ''), 
            ];
        }

        // --- CSV Generation and Download ---
        $filename = "salary_report_{$selected_year_month}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($export_data)) {
            // Write headers
            fputcsv($output, array_keys($export_data[0]));
        }
        
        // Write data rows
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
        
        // --- START CORRECTED SALARY LOGIC ---
        // 1. Fetch the Daily Wage records for the selected month
        $selected_month_db_format = $selected_year_month . '-01'; // Converts 'YYYY-MM' to 'YYYY-MM-01'
        $monthwise_salaries = $this->model->selectDataWithCondition(
            'monthwise_salary',
            ['salary_month' => $selected_month_db_format] // Uses the correct column name and format
        );

        // 2. Create a map of employee_id => daily_wage
        $daily_wage_map = []; // Renamed for clarity
        foreach ($monthwise_salaries as $salary_record) {
            $daily_wage_map[$salary_record->employee_id] = (float)($salary_record->salary ?? 0.00);
        }
        // --- END CORRECTED SALARY LOGIC ---

        // --- Data Collection ---
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
            
            // --- UPDATED LOGIC: Use daily wage directly ---
            $daily_wage = $daily_wage_map[$employee_id] ?? 0.00;
            $daily_salary_factor = $daily_wage; // No division
            
            $monthly_report_map[$employee_id]['total_earnings'] += ((float)$record->daily_attendance * $daily_salary_factor);
            // --- END UPDATED LOGIC ---
        }
        
        // --- Final Data Preparation ---
        $export_data = [];
        $grand_total = 0.00; 

        // Set the custom headers (first row of the array)
        $export_data[] = [
            'Employee Name',
            'Bank Account Number',
            'Bank IFSC Code',
            'Net Due' 
        ];
        
        foreach ($employee_list as $employee) {
            $employee_id = $employee->employee_id;
            $report = $monthly_report_map[$employee_id] ?? null;
            
            // Filter 1: Only include employee if they are Active OR have an entry this month
            if ($report === null && $employee->active != 1) {
                continue;
            }

            $total_earnings = $report['total_earnings'] ?? 0.00;
            $total_extra = $report['total_extra'] ?? 0.00;
            $total_advance = $report['total_advance'] ?? 0.00;
            
            $net_due = $total_earnings + $total_extra - $total_advance;

            // Filter 2: Only include records where net_due is greater than zero
            if ($net_due <= 0.00) {
                continue; 
            }

            // Accumulate grand total for positive payments
            $grand_total += $net_due;

            // Prepare the array row for Excel processing.
            $export_data[] = [
                'Employee Name' => strtoupper($employee->employee_name),
                'Bank Account Number' => (string)$employee->bank_ac_number, 
                'Bank IFSC Code' => strtoupper((string)$employee->bank_ifsc_code),
                'Net Due' => $net_due, // Raw numeric value
            ];
        }

        // Add the TOTAL row at the end
        $export_data[] = [
            'Employee Name' => 'TOTAL',
            'Bank Account Number' => '',
            'Bank IFSC Code' => '',
            'Net Due' => $grand_total,
        ];


        // --- XLSX Generation and Download Setup (Requires PhpOffice\PhpSpreadsheet) ---
        // This section assumes PhpOffice\PhpSpreadsheet is loaded and available
        
        $filename = "salary_report_{$selected_year_month}.xlsx";
        
        // Set headers for XLSX download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0'); 

        // Initialize Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Populate data starting from A1 (includes headers)
        $sheet->fromArray($export_data, NULL, 'A1');

        // Apply necessary formatting
        $last_data_row = count($export_data);

        // Auto size columns for readability
        foreach (range('A', 'D') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Format Column B (Bank AC Number) and C (IFSC) as TEXT to prevent data loss.
        $sheet->getStyle('B2:C' . $last_data_row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        
        // Format Column D (Net Due) as a simple number with 2 decimals (applies to the whole column)
        $sheet->getStyle('D:D')->getNumberFormat()->setFormatCode('0.00'); 
        

        // Create the writer and stream the file
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit();
    }

    public function monthly_salary_crud()
    {
        $this->enforceLogin(); 
        
        $status_message = '';
        $error_message = '';
        
        // Handle URL messages (status/error from redirect)
        if (isset($_REQUEST['status_msg'])) {
            $status_message = htmlspecialchars($_REQUEST['status_msg']);
        }
        if (isset($_REQUEST['error_msg'])) {
            $error_message = htmlspecialchars($_REQUEST['error_msg']);
        }

        // 1. Determine the selected month (YYYY-MM format) and calculate navigation links
        $current_date = new \DateTime();
        $current_year_month = $current_date->format('Y-m');
        
        $selected_year_month_display = isset($_REQUEST['month']) ? $_REQUEST['month'] : $current_year_month;
        // Convert to YYYY-MM-01 format for database queries
        $selected_month_db_format = $selected_year_month_display . '-01'; 
        
        // --- Calculate Prev/Next Navigation Months ---
        $selected_dt = new \DateTime($selected_month_db_format);
        
        // Calculate Previous Month
        $selected_dt_prev = clone $selected_dt;
        $prev_month = $selected_dt_prev->modify('-1 month')->format('Y-m'); 
        
        // Calculate Next Month
        $selected_dt_next = new \DateTime($selected_month_db_format);
        $next_month = $selected_dt_next->modify('+1 month')->format('Y-m'); 
        
        // // Prevent navigating to future months
        // if ($next_month > $current_year_month) {
        //     $next_month = $current_year_month;
        // }
        // // --- End Navigation Calculation ---


        // 2. Handle POST Request (Salary Update) using transactions
        if (isset($_POST['save_salaries']) && isset($_POST['salaries'])) {
            $data_to_save = $_POST['salaries'];
            
            $this->model->beginTransaction();

            try {
                foreach ($data_to_save as $employee_id => $salary_value) {
                    $employee_id = (int)$employee_id;
                    
                    // CRITICAL LOGIC: Treat empty entry as 0.00
                    $salary_input = trim($salary_value); 
                    $salary = 0.00;
                    if ($salary_input !== '') {
                        // Sanitize input
                        $salary = (float)(filter_var($salary_input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?? 0.00);
                    }
                    
                    if ($salary < 0) continue; 

                    $data = [
                        'employee_id' => $employee_id,
                        'salary' => $salary,
                        'salary_month' => $selected_month_db_format
                    ];
                    
                    // Check if a record already exists
                    $existing = $this->model->selectOne(
                        'monthwise_salary',
                        ['employee_id' => $employee_id, 'salary_month' => $selected_month_db_format]
                    );

                    if ($existing) {
                        // Scenario 1: Record exists. Update it.
                        $update_data = ['salary' => $salary];
                        // Update only if the salary value has changed
                        if (number_format($existing->salary, 2, '.', '') != number_format($salary, 2, '.', '')) { 
                            $result = $this->model->updateData(
                                'monthwise_salary',
                                $update_data,
                                ['id' => $existing->id]
                            );
                        } else {
                            $result = true; // No actual change needed
                        }
                    } else {
                        // Scenario 2: Record does not exist. Insert it (critical for saving 0.00 explicitly).
                        $result = $this->model->insertData('monthwise_salary', $data);
                    }
                    
                    if ($result === false) {
                        throw new \Exception("Database error during insert/update for Employee ID: $employee_id.");
                    }
                }
                
                $this->model->commit();
                $status_message = "Successfully updated/inserted salaries for " . date('F Y', strtotime($selected_month_db_format));

            } catch (\Exception $e) {
                $this->model->rollBack();
                $error_message = "Error saving salaries: " . $e->getMessage() . ". All changes rolled back.";
            }

            // Redirect to prevent form resubmission
            header("Location: monthly_salary_crud?month=$selected_year_month_display&status_msg=" . urlencode($status_message) . "&error_msg=" . urlencode($error_message));
            exit();
        }

        // 3. Fetch all employees
        $employees = $this->model->selectData('employees_list', ['order_by' => 'employee_name ASC']); 

        // 4. Fetch existing salary records for the selected month
        $existing_salaries = $this->model->selectDataWithCondition(
            'monthwise_salary',
            ['salary_month' => $selected_month_db_format]
        );
        
        // Map existing salaries by employee ID
        $salary_map = [];
        foreach ($existing_salaries as $record) {
            $salary_map[$record->employee_id] = $record->salary;
        }

        // 5. AUTO-POPULATE LOGIC: If the selected month is empty, load data from the latest saved preceding month.
        if (empty($salary_map)) {
            
            // **FIXED LOGIC**: Use the existing selectDataCustom to find the latest previous month.
            $sql = "
                SELECT DISTINCT salary_month 
                FROM monthwise_salary
                WHERE salary_month < '{$selected_month_db_format}'
                ORDER BY salary_month DESC
                LIMIT 1
            ";
            
            // selectDataCustom returns an array, so we check the first element.
            $latest_prev_month_records = $this->model->selectDataCustom($sql);

            if (!empty($latest_prev_month_records)) {
                $latest_prev_month_record = $latest_prev_month_records[0];
                $previous_month_date = $latest_prev_month_record->salary_month;
                $previous_month_display = date('F Y', strtotime($previous_month_date));

                // Fetch this latest previous month's salary data using a standard method
                $previous_salaries = $this->model->selectDataWithCondition(
                    'monthwise_salary',
                    ['salary_month' => $previous_month_date]
                );

                if (!empty($previous_salaries)) {
                    // Populate salary_map with latest saved data
                    $salary_map = [];
                    foreach ($previous_salaries as $record) {
                        $salary_map[$record->employee_id] = $record->salary;
                    }
                    // Set status message for user feedback
                    $status_message = (empty($status_message) ? '' : $status_message . ' | ') . " Salaries auto-populated from $previous_month_display. Edit and click Save to confirm for the current month.";
                }
            }
        }
        
        // Prepare data for the view
        $data = [
            'employees' => $employees,
            'salary_map' => $salary_map,
            'selected_year_month_display' => $selected_year_month_display,
            'selected_month_db_format' => $selected_month_db_format, 
            'status_message' => $status_message,
            'error_message' => $error_message,
            'prev_month' => $prev_month,
            'next_month' => $next_month,
            'current_year_month' => $current_year_month,
        ];
        
        // Load the view
        extract($data);
        include ('app/views/monthly_salary_crud.php'); 
    }

}
?>
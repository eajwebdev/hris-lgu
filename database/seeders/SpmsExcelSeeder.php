<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Office;
use App\Models\Employee;
use App\Models\SpmsOpcr;
use App\Models\SpmsOpcrItem;
use App\Models\SpmsIpcr;
use App\Models\SpmsIpcrItem;

class SpmsExcelSeeder extends Seeder
{
    /**
     * Run the database seeds based on Updated OPCR-and-IPCR-Form-Ma'am Cris.xlsx.
     */
    public function run(): void
    {
        // 1. Get or create target office (e.g. HRMO / GSO or Office 3)
        $office = Office::firstOrCreate(
            ['id' => 3],
            ['office_name' => 'General Services & Human Resource Management Office', 'office_abbr' => 'GSO/HRMO']
        );

        // Find or create Office Head
        $head = Employee::firstOrCreate(
            ['emp_dept' => 3, 'fname' => 'LUCRECIA', 'lname' => 'NICOLAS'],
            ['position' => 'MGDH-I (GSO) / HRMO-Designate', 'stat_1' => 1]
        );

        // Update office head
        $office->update(['office_head_id' => $head->id]);

        // 2. Create OPCR Form for 2026 Semester 1
        $opcr = SpmsOpcr::firstOrCreate(
            [
                'office_id' => $office->id,
                'year' => 2026,
                'semester' => 1,
            ],
            [
                'office_head_id' => $head->id,
                'status' => 'Submitted',
                'total_core_score' => 4.027,
                'total_support_score' => 0.433,
                'final_numerical_rating' => 4.46,
                'final_adjectival_rating' => 'VS',
            ]
        );

        // Clear existing items for clean re-seeding
        SpmsOpcrItem::where('opcr_id', $opcr->id)->delete();

        // 3. Extracted Items from Excel Sheet
        $items = [
            // CORE FUNCTIONS (60%)
            [
                'category' => 'Core Functions',
                'subcategory' => 'POLICY AND PROGRAM IMPLEMENTATION',
                'mfo_pap' => '• Translates national and local policies into actionable plans, program and projects within the department and overseeing the execution of these initiatives to ensure they are carried out effectively and efficiently',
                'success_indicators' => '100% of national and local policies are translated into actionable programs and projects and execution of these initiatives are fully supervised',
                'allotted_budget' => 'All Office',
                'division_accountable' => 'All Office Staff',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 5, 'rating_ave' => 4.33,
                'remarks' => '95% of national and local policies were translated into actionable programs and projects and execution fully supervised',
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'OPERATIONAL MANAGEMENT',
                'mfo_pap' => '• Directs and supervises the daily operations of the department',
                'success_indicators' => '100% of the staff are given directions and specific tasks for the operations of the department and completely supervised',
                'allotted_budget' => 'All Office',
                'division_accountable' => 'All Office Staff',
                'rating_q' => 5, 'rating_e' => 5, 'rating_t' => 5, 'rating_ave' => 5.00,
                'remarks' => '100% of staff given directions and supervised',
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'SERVICE DELIVERY AND PUBLIC ENGAGEMENT',
                'mfo_pap' => '• Ensures effective and efficient delivery of the basic services of the office',
                'success_indicators' => '100% of the basic services of the office are effectively and efficiently carried out',
                'allotted_budget' => 'All Office',
                'division_accountable' => 'All Office Staff',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
                'remarks' => '100% of basic services carried out with 90% efficiency',
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'SERVICE DELIVERY AND PUBLIC ENGAGEMENT',
                'mfo_pap' => '• Facilitates coordination among various stakeholders of the LGU including the community and other government entities',
                'success_indicators' => '100% of meetings, conferences, assemblies and the like involving various stakeholders are attended to',
                'allotted_budget' => 'All Office',
                'division_accountable' => 'All Office Staff',
                'rating_q' => 5, 'rating_e' => 5, 'rating_t' => 5, 'rating_ave' => 5.00,
                'remarks' => '100% of stakeholder meetings attended to',
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'PERSONNEL MANAGEMENT',
                'mfo_pap' => '• Ensures that office personnel are continuously undergoing training/mentoring/coaching sessions to ensure professional growth',
                'success_indicators' => '100% of the personnel are trained, mentored, and coached',
                'allotted_budget' => 'All Office',
                'division_accountable' => 'HRMO Staff',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
                'remarks' => '90% of personnel were trained and mentored',
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'PERSONNEL MANAGEMENT',
                'mfo_pap' => '• Ensures that personnel performance evaluation is given paramount importance',
                'success_indicators' => '100% of the IPCRFs are reviewed and ratings are agreed upon by the personnel concerned',
                'allotted_budget' => 'All Office',
                'division_accountable' => 'HRMO Staff',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.00,
                'remarks' => '100% of IPCRFs reviewed and agreed upon',
            ],
            [
                'category' => 'Core Functions',
                'subcategory' => 'FINANCIAL RESOURCE MANAGEMENT',
                'mfo_pap' => '• Prepares budget, monitoring the use of funds and controlling expenditures to ensure fiscal responsibility and sustainability',
                'success_indicators' => 'Shall have prepared annual budget and managed funds effectively and efficiently',
                'allotted_budget' => '50,000',
                'division_accountable' => 'GSO / HRMO',
                'rating_q' => 5, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.33,
                'remarks' => 'Prepared annual budget and managed funds effectively',
            ],

            // SUPPORT FUNCTIONS (20%)
            [
                'category' => 'Support Functions',
                'subcategory' => 'COMPLIANCE AND REGULATION',
                'mfo_pap' => '• Ensures that all departmental operations and staff adhere to established rules, regulations, and laws',
                'success_indicators' => 'Shall have ensured that rules, regulations, and laws established in the office are fully adhered to',
                'allotted_budget' => 'All Office',
                'division_accountable' => 'All Staff',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.00,
                'remarks' => '100% of established rules fully adhered to',
            ],
            [
                'category' => 'Support Functions',
                'subcategory' => 'COMPLIANCE AND REGULATION',
                'mfo_pap' => '• Submit reports required by ARTA, DILG and DICT',
                'success_indicators' => '100% of correctly-prepared reports required by DILG, ARTA and DICT shall have been submitted on time.',
                'allotted_budget' => 'All Office',
                'division_accountable' => 'ARTA / HRMO Focal',
                'rating_q' => 5, 'rating_e' => 5, 'rating_t' => 4, 'rating_ave' => 4.67,
                'remarks' => '100% of reports submitted on time (Citizen Charter, Zero Backlog, CSM)',
            ],
            [
                'category' => 'Support Functions',
                'subcategory' => 'HUMAN RESOURCE MANAGEMENT',
                'mfo_pap' => '• Ensure timely submission of Form 6 computation of leave credits',
                'success_indicators' => '100% of the Form 6 shall have been computed and submitted properly.',
                'allotted_budget' => 'All Office',
                'division_accountable' => 'HR Staff',
                'rating_q' => 5, 'rating_e' => 5, 'rating_t' => 4, 'rating_ave' => 4.67,
                'remarks' => '100% of Form 6 computed and submitted properly',
            ],
            [
                'category' => 'Support Functions',
                'subcategory' => 'HUMAN RESOURCE MANAGEMENT',
                'mfo_pap' => '• Implement the monthly Moral Recovery Program activity as part of the Human Resource Capacity Enhancement Initiative',
                'success_indicators' => '100% of the Moral Recovery Program activities designed for the semester shall have been carried out',
                'allotted_budget' => '10,000',
                'division_accountable' => 'HR Staff',
                'rating_q' => 5, 'rating_e' => 5, 'rating_t' => 5, 'rating_ave' => 5.00,
                'remarks' => '100% of Moral Recovery Program activity carried out',
            ],
            [
                'category' => 'Support Functions',
                'subcategory' => 'SERVICE DELIVERY AND PUBLIC ENGAGEMENT',
                'mfo_pap' => '• Attends flag raising and lowering ceremonies',
                'success_indicators' => '100% of required flag raising and lowering ceremonies shall have been attended.',
                'allotted_budget' => 'N/A',
                'division_accountable' => 'All Staff',
                'rating_q' => 4, 'rating_e' => 4, 'rating_t' => 4, 'rating_ave' => 4.00,
                'remarks' => 'Attended 98% of flag ceremonies',
            ],
        ];

        foreach ($items as $data) {
            SpmsOpcrItem::create(array_merge($data, ['opcr_id' => $opcr->id]));
        }

        echo "SPMS Excel Seeder completed successfully! Added " . count($items) . " OPCR items from Excel.\n";
    }
}

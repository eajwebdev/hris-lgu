<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. pds_references
        if (Schema::hasTable('pds_references')) {
            Schema::table('pds_references', function (Blueprint $table) {
                if (!Schema::hasColumn('pds_references', 'empid')) $table->string('empid')->nullable();
                if (!Schema::hasColumn('pds_references', 'refname')) $table->text('refname')->nullable();
                if (!Schema::hasColumn('pds_references', 'refadd')) $table->text('refadd')->nullable();
                if (!Schema::hasColumn('pds_references', 'reftelno')) $table->text('reftelno')->nullable();
            });
        }

        // 2. gov_ids
        if (Schema::hasTable('gov_ids')) {
            Schema::table('gov_ids', function (Blueprint $table) {
                if (!Schema::hasColumn('gov_ids', 'empid')) $table->string('empid')->nullable();
                if (!Schema::hasColumn('gov_ids', 'govid')) $table->string('govid')->nullable();
            });
        }

        // 3. official_times
        if (Schema::hasTable('official_times')) {
            Schema::table('official_times', function (Blueprint $table) {
                if (!Schema::hasColumn('official_times', 'empid')) $table->string('empid')->nullable();
                if (!Schema::hasColumn('official_times', 'morn_mon')) $table->string('morn_mon')->nullable();
                if (!Schema::hasColumn('official_times', 'aft_mon')) $table->string('aft_mon')->nullable();
                if (!Schema::hasColumn('official_times', 'morn_tue')) $table->string('morn_tue')->nullable();
                if (!Schema::hasColumn('official_times', 'aft_tue')) $table->string('aft_tue')->nullable();
                if (!Schema::hasColumn('official_times', 'morn_wed')) $table->string('morn_wed')->nullable();
                if (!Schema::hasColumn('official_times', 'aft_wed')) $table->string('aft_wed')->nullable();
                if (!Schema::hasColumn('official_times', 'morn_thu')) $table->string('morn_thu')->nullable();
                if (!Schema::hasColumn('official_times', 'aft_thu')) $table->string('aft_thu')->nullable();
                if (!Schema::hasColumn('official_times', 'morn_fri')) $table->string('morn_fri')->nullable();
                if (!Schema::hasColumn('official_times', 'aft_fri')) $table->string('aft_fri')->nullable();
            });
        }

        // 4. settings
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (!Schema::hasColumn('settings', 'hr')) $table->integer('hr')->nullable();
                if (!Schema::hasColumn('settings', 'mayor')) $table->integer('mayor')->nullable();
                if (!Schema::hasColumn('settings', 'vice_mayor')) $table->integer('vice_mayor')->nullable();
                if (!Schema::hasColumn('settings', 'dtr_acct')) $table->string('dtr_acct')->nullable();
                if (!Schema::hasColumn('settings', 'hr_kiosk')) $table->string('hr_kiosk')->nullable();
                if (!Schema::hasColumn('settings', 'hrk_pw')) $table->string('hrk_pw')->nullable();
                if (!Schema::hasColumn('settings', 'sync_backups')) $table->string('sync_backups')->nullable();
                if (!Schema::hasColumn('settings', 'te_rstrct_lvl')) $table->string('te_rstrct_lvl')->nullable();
                if (!Schema::hasColumn('settings', 'records_office_email')) $table->string('records_office_email')->nullable();
                if (!Schema::hasColumn('settings', 'job_portal_email')) $table->string('job_portal_email')->nullable();
                if (!Schema::hasColumn('settings', 'maintenance')) $table->integer('maintenance')->default(0);
            });
        }

        // 5. leave_credits
        if (Schema::hasTable('leave_credits')) {
            Schema::table('leave_credits', function (Blueprint $table) {
                if (!Schema::hasColumn('leave_credits', 'empid')) $table->string('empid')->nullable();
                if (!Schema::hasColumn('leave_credits', 'days')) $table->decimal('days', 8, 2)->default(0);
                if (!Schema::hasColumn('leave_credits', 'earn_sl')) $table->decimal('earn_sl', 8, 2)->default(0);
                if (!Schema::hasColumn('leave_credits', 'earn_vl')) $table->decimal('earn_vl', 8, 2)->default(0);
                if (!Schema::hasColumn('leave_credits', 'date')) $table->date('date')->nullable();
                if (!Schema::hasColumn('leave_credits', 'remarks')) $table->text('remarks')->nullable();
                if (!Schema::hasColumn('leave_credits', 'add_by')) $table->string('add_by')->nullable();
                if (!Schema::hasColumn('leave_credits', 'stat')) $table->string('stat')->nullable();
            });
        }

        // 6. leave_applications
        if (Schema::hasTable('leave_applications')) {
            Schema::table('leave_applications', function (Blueprint $table) {
                if (!Schema::hasColumn('leave_applications', 'transnum')) $table->string('transnum')->nullable();
                if (!Schema::hasColumn('leave_applications', 'empid')) $table->string('empid')->nullable();
                if (!Schema::hasColumn('leave_applications', 'position')) $table->string('position')->nullable();
                if (!Schema::hasColumn('leave_applications', 'salary')) $table->decimal('salary', 12, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'leave_type')) $table->string('leave_type')->nullable();
                if (!Schema::hasColumn('leave_applications', 'leave_purpose')) $table->string('leave_purpose')->nullable();
                if (!Schema::hasColumn('leave_applications', 'leave_detail')) $table->text('leave_detail')->nullable();
                if (!Schema::hasColumn('leave_applications', 'date_range')) $table->text('date_range')->nullable();
                if (!Schema::hasColumn('leave_applications', 'days')) $table->decimal('days', 8, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'commutation')) $table->string('commutation')->nullable();
                if (!Schema::hasColumn('leave_applications', 'total_vl')) $table->decimal('total_vl', 8, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'total_sl')) $table->decimal('total_sl', 8, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'less_vl')) $table->decimal('less_vl', 8, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'less_sl')) $table->decimal('less_sl', 8, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'recommend')) $table->string('recommend')->nullable();
                if (!Schema::hasColumn('leave_applications', 'emp_esign')) $table->string('emp_esign')->nullable();
                if (!Schema::hasColumn('leave_applications', 'supervisor')) $table->integer('supervisor')->nullable();
                if (!Schema::hasColumn('leave_applications', 'oic')) $table->integer('oic')->nullable();
                if (!Schema::hasColumn('leave_applications', 'sup_prefix')) $table->string('sup_prefix')->nullable();
                if (!Schema::hasColumn('leave_applications', 'sup_sign')) $table->integer('sup_sign')->nullable();
                if (!Schema::hasColumn('leave_applications', 'sup_sdate')) $table->string('sup_sdate')->nullable();
                if (!Schema::hasColumn('leave_applications', 'approver')) $table->integer('approver')->nullable();
                if (!Schema::hasColumn('leave_applications', 'approver_prefix')) $table->string('approver_prefix')->nullable();
                if (!Schema::hasColumn('leave_applications', 'approver_sign')) $table->integer('approver_sign')->nullable();
                if (!Schema::hasColumn('leave_applications', 'approver_sdate')) $table->string('approver_sdate')->nullable();
                if (!Schema::hasColumn('leave_applications', 'approver_role')) $table->string('approver_role')->nullable();
                if (!Schema::hasColumn('leave_applications', 'hr')) $table->integer('hr')->nullable();
                if (!Schema::hasColumn('leave_applications', 'hr_prefix')) $table->string('hr_prefix')->nullable();
                if (!Schema::hasColumn('leave_applications', 'hr_sign')) $table->integer('hr_sign')->nullable();
                if (!Schema::hasColumn('leave_applications', 'hr_sdate')) $table->string('hr_sdate')->nullable();
                if (!Schema::hasColumn('leave_applications', 'remarks_stat')) $table->string('remarks_stat')->nullable();
                if (!Schema::hasColumn('leave_applications', 'remarks_details')) $table->text('remarks_details')->nullable();
                if (!Schema::hasColumn('leave_applications', 'remarks_details1')) $table->text('remarks_details1')->nullable();
                if (!Schema::hasColumn('leave_applications', 'remarks_details2')) $table->text('remarks_details2')->nullable();
                if (!Schema::hasColumn('leave_applications', 'department')) $table->string('department')->nullable();
                if (!Schema::hasColumn('leave_applications', 'date_filing')) $table->date('date_filing')->nullable();
                if (!Schema::hasColumn('leave_applications', 'day_wpay')) $table->decimal('day_wpay', 8, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'earn')) $table->decimal('earn', 8, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'less')) $table->decimal('less', 8, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'balance')) $table->decimal('balance', 8, 2)->nullable();
                if (!Schema::hasColumn('leave_applications', 'status')) $table->string('status')->nullable();
                if (!Schema::hasColumn('leave_applications', 'gen_app')) $table->string('gen_app')->nullable();
                if (!Schema::hasColumn('leave_applications', 'as_of')) $table->date('as_of')->nullable();
                if (!Schema::hasColumn('leave_applications', 'holiday')) $table->string('holiday')->nullable();
            });
        }

        // 7. applications
        if (Schema::hasTable('applications')) {
            Schema::table('applications', function (Blueprint $table) {
                if (!Schema::hasColumn('applications', 'jid')) $table->unsignedBigInteger('jid')->nullable();
                if (!Schema::hasColumn('applications', 'app_number')) $table->string('app_number')->nullable();
                if (!Schema::hasColumn('applications', 'position')) $table->string('position')->nullable();
                if (!Schema::hasColumn('applications', 'first_name')) $table->string('first_name')->nullable();
                if (!Schema::hasColumn('applications', 'middle_name')) $table->string('middle_name')->nullable();
                if (!Schema::hasColumn('applications', 'last_name')) $table->string('last_name')->nullable();
                if (!Schema::hasColumn('applications', 'age')) $table->integer('age')->nullable();
                if (!Schema::hasColumn('applications', 'sex')) $table->string('sex')->nullable();
                if (!Schema::hasColumn('applications', 'mobile')) $table->string('mobile')->nullable();
                if (!Schema::hasColumn('applications', 'email')) $table->string('email')->nullable();
                if (!Schema::hasColumn('applications', 'address')) $table->text('address')->nullable();
                if (!Schema::hasColumn('applications', 'education')) $table->text('education')->nullable();
                if (!Schema::hasColumn('applications', 'eligibility')) $table->text('eligibility')->nullable();
                if (!Schema::hasColumn('applications', 'pds')) $table->string('pds')->nullable();
                if (!Schema::hasColumn('applications', 'wes')) $table->string('wes')->nullable();
                if (!Schema::hasColumn('applications', 'intent')) $table->string('intent')->nullable();
                if (!Schema::hasColumn('applications', 'resume')) $table->string('resume')->nullable();
                if (!Schema::hasColumn('applications', 'tor')) $table->string('tor')->nullable();
                if (!Schema::hasColumn('applications', 'coe')) $table->string('coe')->nullable();
                if (!Schema::hasColumn('applications', 'cert_training')) $table->string('cert_training')->nullable();
                if (!Schema::hasColumn('applications', 'dq_reason')) $table->text('dq_reason')->nullable();
                if (!Schema::hasColumn('applications', 'ctrl_no')) $table->string('ctrl_no')->nullable();
                if (!Schema::hasColumn('applications', 'interview_datetime')) $table->dateTime('interview_datetime')->nullable();
                if (!Schema::hasColumn('applications', 'venue')) $table->string('venue')->nullable();
                if (!Schema::hasColumn('applications', 'status')) $table->integer('status')->default(0);
                if (!Schema::hasColumn('applications', 'checked')) $table->integer('checked')->default(0);
                if (!Schema::hasColumn('applications', 'is_complete')) $table->integer('is_complete')->default(0);
            });
        }

        // 8. job_hirings
        if (Schema::hasTable('job_hirings')) {
            Schema::table('job_hirings', function (Blueprint $table) {
                if (!Schema::hasColumn('job_hirings', 'type')) $table->string('type')->nullable();
                if (!Schema::hasColumn('job_hirings', 'title')) $table->string('title')->nullable();
                if (!Schema::hasColumn('job_hirings', 'plantilla_item_no')) $table->string('plantilla_item_no')->nullable();
                if (!Schema::hasColumn('job_hirings', 'salary')) $table->decimal('salary', 12, 2)->nullable();
                if (!Schema::hasColumn('job_hirings', 'assignment')) $table->string('assignment')->nullable();
                if (!Schema::hasColumn('job_hirings', 'education')) $table->text('education')->nullable();
                if (!Schema::hasColumn('job_hirings', 'eligibility')) $table->text('eligibility')->nullable();
                if (!Schema::hasColumn('job_hirings', 'training')) $table->text('training')->nullable();
                if (!Schema::hasColumn('job_hirings', 'experience')) $table->text('experience')->nullable();
                if (!Schema::hasColumn('job_hirings', 'competency')) $table->text('competency')->nullable();
                if (!Schema::hasColumn('job_hirings', 'posted_at')) $table->date('posted_at')->nullable();
                if (!Schema::hasColumn('job_hirings', 'expiration_at')) $table->date('expiration_at')->nullable();
                if (!Schema::hasColumn('job_hirings', 'status')) $table->string('status')->default('active');
            });
        }

        // 9. logzones
        if (Schema::hasTable('logzones')) {
            Schema::table('logzones', function (Blueprint $table) {
                if (!Schema::hasColumn('logzones', 'points')) $table->text('points')->nullable();
                if (!Schema::hasColumn('logzones', 'camp_id')) $table->unsignedBigInteger('camp_id')->nullable();
                if (!Schema::hasColumn('logzones', 'label')) $table->string('label')->nullable();
            });
        }

        // 10. eligibilities
        if (Schema::hasTable('eligibilities')) {
            Schema::table('eligibilities', function (Blueprint $table) {
                if (!Schema::hasColumn('eligibilities', 'careereligible')) $table->string('careereligible')->nullable();
                if (!Schema::hasColumn('eligibilities', 'remarks')) $table->text('remarks')->nullable();
            });
        }

        // 11. ete_evaluations
        if (Schema::hasTable('ete_evaluations')) {
            Schema::table('ete_evaluations', function (Blueprint $table) {
                if (!Schema::hasColumn('ete_evaluations', 'off_id')) $table->unsignedBigInteger('off_id')->nullable();
            });
        }

        // 12. learning_devs
        if (Schema::hasTable('learning_devs')) {
            Schema::table('learning_devs', function (Blueprint $table) {
                if (!Schema::hasColumn('learning_devs', 'learning_dev')) $table->text('learning_dev')->nullable();
                if (!Schema::hasColumn('learning_devs', 'inc_date1')) $table->string('inc_date1')->nullable();
                if (!Schema::hasColumn('learning_devs', 'inc_date2')) $table->string('inc_date2')->nullable();
                if (!Schema::hasColumn('learning_devs', 'num_hours')) $table->string('num_hours')->nullable();
                if (!Schema::hasColumn('learning_devs', 'types')) $table->string('types')->nullable();
                if (!Schema::hasColumn('learning_devs', 'conducted')) $table->string('conducted')->nullable();
                if (!Schema::hasColumn('learning_devs', 'attachment')) $table->string('attachment')->nullable();
                if (!Schema::hasColumn('learning_devs', 'status')) $table->string('status')->nullable();
                if (!Schema::hasColumn('learning_devs', 'remarks')) $table->text('remarks')->nullable();
            });
        }

        // 13. voluntary_works
        if (Schema::hasTable('voluntary_works')) {
            Schema::table('voluntary_works', function (Blueprint $table) {
                if (!Schema::hasColumn('voluntary_works', 'org_name')) $table->text('org_name')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'inc_date1')) $table->string('inc_date1')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'inc_date2')) $table->string('inc_date2')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'num_hours')) $table->string('num_hours')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'position')) $table->string('position')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'attachment')) $table->string('attachment')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'status')) $table->string('status')->nullable();
                if (!Schema::hasColumn('voluntary_works', 'remarks')) $table->text('remarks')->nullable();
            });
        }

        // 14. work_experiences
        if (Schema::hasTable('work_experiences')) {
            Schema::table('work_experiences', function (Blueprint $table) {
                if (!Schema::hasColumn('work_experiences', 'inc_date1')) $table->string('inc_date1')->nullable();
                if (!Schema::hasColumn('work_experiences', 'inc_date2')) $table->string('inc_date2')->nullable();
                if (!Schema::hasColumn('work_experiences', 'department')) $table->string('department')->nullable();
                if (!Schema::hasColumn('work_experiences', 'salary')) $table->string('salary')->nullable();
                if (!Schema::hasColumn('work_experiences', 'sg_grade')) $table->string('sg_grade')->nullable();
                if (!Schema::hasColumn('work_experiences', 'attachment')) $table->string('attachment')->nullable();
                if (!Schema::hasColumn('work_experiences', 'stat_app')) $table->string('stat_app')->nullable();
                if (!Schema::hasColumn('work_experiences', 'status')) $table->string('status')->nullable();
                if (!Schema::hasColumn('work_experiences', 'service')) $table->string('service')->nullable();
                if (!Schema::hasColumn('work_experiences', 'supervisor')) $table->string('supervisor')->nullable();
                if (!Schema::hasColumn('work_experiences', 'list_accom')) $table->text('list_accom')->nullable();
                if (!Schema::hasColumn('work_experiences', 'actual_summary')) $table->text('actual_summary')->nullable();
                if (!Schema::hasColumn('work_experiences', 'remarks')) $table->text('remarks')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};

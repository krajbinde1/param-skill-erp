<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('code_sequences')->insertOrIgnore([
            'name' => 'student',
            'last_value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code', 20)->unique();
            $table->uuid('client_uuid')->nullable();
            $table->foreignId('centre_id')->nullable()->constrained('centres')->nullOnDelete();
            $table->foreignId('preferred_centre_id')->nullable()->constrained('centres')->nullOnDelete();
            $table->foreignId('mobilizer_id')->constrained('employees')->restrictOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('mobile', 15);
            $table->string('parent_mobile', 15)->nullable();
            $table->string('alternate_mobile', 15)->nullable();
            $table->string('email')->nullable();
            $table->text('aadhaar_encrypted')->nullable();
            $table->string('aadhaar_hash', 64)->nullable();
            $table->string('aadhaar_last4', 4)->nullable();
            $table->text('full_address');
            $table->string('village');
            $table->string('taluka');
            $table->string('district');
            $table->string('state')->default('Maharashtra');
            $table->string('pincode', 10)->nullable();
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->string('category')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('education_qualification')->nullable();
            $table->string('school_college_name')->nullable();
            $table->string('passing_year', 10)->nullable();
            $table->string('percentage_grade')->nullable();
            $table->string('employment_status')->nullable();
            $table->decimal('annual_family_income', 12, 2)->nullable();
            $table->string('preferred_course')->nullable();
            $table->boolean('hostel_required')->default(false);
            $table->string('student_photo')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation')->nullable();
            $table->string('guardian_mobile', 15)->nullable();
            $table->string('admission_status');
            $table->string('verification_status');
            $table->string('centre_visit_status');
            $table->date('next_follow_up_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('remark')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['mobilizer_id', 'client_uuid']);
            $table->index('centre_id');
            $table->index('preferred_centre_id');
            $table->index('mobilizer_id');
            $table->index('mobile');
            $table->unique('aadhaar_hash');
            $table->index('district');
            $table->index('taluka');
            $table->index('admission_status');
            $table->index('verification_status');
            $table->index('centre_visit_status');
            $table->index('next_follow_up_date');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
        DB::table('code_sequences')->where('name', 'student')->delete();
    }
};

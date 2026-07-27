<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centre_id')->constrained('centres')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_code', 20)->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('father_husband_name')->nullable();
            $table->string('mobile', 15);
            $table->string('alternate_mobile', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('aadhaar_number_encrypted')->nullable();
            $table->string('aadhaar_hash', 64)->nullable();
            $table->string('aadhaar_last4', 4)->nullable();
            $table->string('pan_number', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('village')->nullable();
            $table->string('taluka')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->default('Maharashtra');
            $table->string('pincode', 10)->nullable();
            $table->string('employee_role');
            $table->date('joining_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('bank_name')->nullable();
            $table->text('account_number')->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('aadhaar_document')->nullable();
            $table->string('pan_document')->nullable();
            $table->string('education_certificate')->nullable();
            $table->string('appointment_letter')->nullable();
            $table->string('other_document')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('centre_id');
            $table->index('mobile');
            $table->unique('aadhaar_hash');
            $table->index('employee_role');
            $table->index('district');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

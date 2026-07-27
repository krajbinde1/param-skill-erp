<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_centre_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('centre_id')->constrained('centres')->restrictOnDelete();
            $table->foreignId('mobilizer_id')->constrained('employees')->restrictOnDelete();
            $table->date('visit_date');
            $table->string('visit_status');
            $table->string('student_photo')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->text('manager_remark')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'visit_date']);
            $table->index('centre_id');
            $table->index('visit_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_centre_visits');
    }
};

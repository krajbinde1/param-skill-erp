<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('mobilizer_id')->constrained('employees')->restrictOnDelete();
            $table->date('follow_up_date');
            $table->boolean('contacted')->default(false);
            $table->text('response')->nullable();
            $table->string('interest_status')->nullable();
            $table->date('centre_visit_date')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->text('remark')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'follow_up_date']);
            $table->index('mobilizer_id');
            $table->index('next_follow_up_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_follow_ups');
    }
};

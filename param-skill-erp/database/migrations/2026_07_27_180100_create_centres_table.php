<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centres', function (Blueprint $table) {
            $table->id();
            $table->string('centre_code', 20)->unique();
            $table->string('centre_name');
            $table->string('scheme_name')->nullable();
            $table->string('project_name')->nullable();
            $table->string('manager_name');
            $table->string('manager_mobile', 15);
            $table->string('manager_email')->nullable();
            $table->text('address');
            $table->string('village_city')->nullable();
            $table->string('taluka')->nullable();
            $table->string('district');
            $table->string('state')->default('Maharashtra');
            $table->string('pincode', 10)->nullable();
            $table->unsignedInteger('centre_capacity')->nullable();
            $table->unsignedInteger('boys_capacity')->nullable();
            $table->unsignedInteger('girls_capacity')->nullable();
            $table->boolean('hostel_available')->default(false);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('opening_date')->nullable();
            $table->date('agreement_start_date')->nullable();
            $table->date('agreement_end_date')->nullable();
            $table->string('centre_photo')->nullable();
            $table->string('agreement_document')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('district');
            $table->index('state');
            $table->index('manager_mobile');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centres');
    }
};

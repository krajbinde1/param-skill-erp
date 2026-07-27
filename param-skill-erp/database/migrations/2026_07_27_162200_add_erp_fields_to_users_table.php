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
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_id')->unique()->after('id');
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->unsignedBigInteger('centre_id')->nullable()->after('last_login_at');
            $table->unsignedBigInteger('employee_id')->nullable()->after('centre_id');
            $table->softDeletes()->after('updated_at');

            $table->index('centre_id');
            $table->index('employee_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['centre_id']);
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['is_active']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'login_id',
                'is_active',
                'must_change_password',
                'last_login_at',
                'centre_id',
                'employee_id',
            ]);
        });
    }
};

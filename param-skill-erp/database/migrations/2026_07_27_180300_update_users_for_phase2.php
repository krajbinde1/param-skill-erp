<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 15)->nullable()->after('email');
            $table->string('status', 20)->default('active')->after('password');
        });

        if (Schema::hasColumn('users', 'is_active')) {
            DB::table('users')->where('is_active', 1)->update(['status' => 'active']);
            DB::table('users')->where('is_active', 0)->update(['status' => 'inactive']);

            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index('mobile');
            $table->index('status');
            $table->foreign('centre_id')->references('id')->on('centres')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['centre_id']);
            $table->dropForeign(['employee_id']);
            $table->dropIndex(['mobile']);
            $table->dropIndex(['status']);
            $table->boolean('is_active')->default(true)->after('password');
        });

        DB::table('users')->update(['is_active' => DB::raw("CASE WHEN status = 'active' THEN 1 ELSE 0 END")]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'status']);
            $table->index('is_active');
        });
    }
};

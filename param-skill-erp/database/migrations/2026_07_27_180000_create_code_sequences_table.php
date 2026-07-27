<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_sequences', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();
        });

        DB::table('code_sequences')->insert([
            ['name' => 'centre', 'last_value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'employee', 'last_value' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('code_sequences');
    }
};

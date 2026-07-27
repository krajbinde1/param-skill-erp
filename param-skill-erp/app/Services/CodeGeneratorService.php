<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class CodeGeneratorService
{
    public function nextCentreCode(): string
    {
        return $this->next('centre', 'PSC', 4);
    }

    public function nextEmployeeCode(): string
    {
        return $this->next('employee', 'EMP', 5);
    }

    public function nextStudentCode(): string
    {
        return $this->next('student', 'STD', 6);
    }

    protected function next(string $sequence, string $prefix, int $padding): string
    {
        return DB::transaction(function () use ($sequence, $prefix, $padding) {
            $row = DB::table('code_sequences')
                ->where('name', $sequence)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('code_sequences')->insert([
                    'name' => $sequence,
                    'last_value' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $row = DB::table('code_sequences')
                    ->where('name', $sequence)
                    ->lockForUpdate()
                    ->first();
            }

            if ($row === null) {
                throw new RuntimeException("Unable to allocate code sequence [{$sequence}].");
            }

            $next = (int) $row->last_value + 1;

            DB::table('code_sequences')
                ->where('name', $sequence)
                ->update([
                    'last_value' => $next,
                    'updated_at' => now(),
                ]);

            return $prefix.str_pad((string) $next, $padding, '0', STR_PAD_LEFT);
        });
    }
}

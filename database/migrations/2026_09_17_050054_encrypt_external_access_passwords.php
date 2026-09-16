<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('external_access')->select('id', 'password')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                if ($row->password === null || $row->password === '') {
                    continue;
                }

                try {
                    Crypt::decryptString($row->password);

                    continue;
                } catch (DecryptException) {
                    // Not encrypted yet, fall through and encrypt it.
                }

                DB::table('external_access')
                    ->where('id', $row->id)
                    ->update(['password' => Crypt::encryptString($row->password)]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('external_access')->select('id', 'password')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                if ($row->password === null || $row->password === '') {
                    continue;
                }

                try {
                    $plain = Crypt::decryptString($row->password);
                } catch (DecryptException) {
                    continue;
                }

                DB::table('external_access')
                    ->where('id', $row->id)
                    ->update(['password' => $plain]);
            }
        });
    }
};

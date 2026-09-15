<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'invitations',
            function (Blueprint $table) {
                $table
                    ->unsignedInteger('scan_limit')
                    ->default(1)
                    ->after('qr_token');

                $table
                    ->unsignedInteger('scan_count')
                    ->default(0)
                    ->after('scan_limit');
            }
        );


        /*
         * Invitation lama yang sudah pernah scan
         * dianggap sudah menggunakan 1x limit.
         */
        DB::table('invitations')
            ->whereNotNull('checked_in_at')
            ->update([
                'scan_count' => 1,
            ]);
    }


    public function down(): void
    {
        Schema::table(
            'invitations',
            function (Blueprint $table) {
                $table->dropColumn([
                    'scan_limit',
                    'scan_count',
                ]);
            }
        );
    }
};

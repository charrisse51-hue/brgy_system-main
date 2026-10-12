<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('suffix')->nullable()->after('last_name');
        });

        // backfill parts from existing full name
        DB::table('residents')->orderBy('id')->chunk(100, function ($residents) {
            foreach ($residents as $r) {
                $full = trim($r->name);
                if ($full === '') {
                    continue;
                }
                $parts = preg_split('/\s+/', $full);
                $first = array_shift($parts);
                $last = '';
                $middle = '';
                $suffix = '';
                if (count($parts) > 0) {
                    // if last part looks like suffix (Jr, Sr, III etc) treat separately
                    $possibleSuffix = end($parts);
                    if (in_array(strtolower($possibleSuffix), ['jr', 'sr', 'ii', 'iii', 'iv', 'v'])) {
                        $suffix = array_pop($parts);
                    }
                    if (count($parts) > 0) {
                        $last = array_pop($parts);
                        if (count($parts) > 0) {
                            $middle = implode(' ', $parts);
                        }
                    }
                }
                DB::table('residents')->where('id', $r->id)->update([
                    'first_name'  => $first,
                    'middle_name' => $middle,
                    'last_name'   => $last,
                    'suffix'      => $suffix,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'suffix']);
        });
    }
};
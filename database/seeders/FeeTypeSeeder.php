<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FeeTypeSeeder extends Seeder
{
    public function run()
    {
        DB::table('fee_types')->insert([
            ['name' => 'Late Return', 'rate' => 5, 'description' => '5 pesos per day late', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Damaged Book', 'rate' => 30, 'description' => '30 pesos for damaged book', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Lost Book', 'rate' => 200, 'description' => '200 pesos for lost book', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
} 
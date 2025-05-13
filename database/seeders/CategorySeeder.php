<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    public function run()
    {
        DB::table('categories')->insert([
            ['category_id' => 1, 'name' => 'Fiction', 'description' => 'Literary works invented by the imagination, such as novels or short stories.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category_id' => 2, 'name' => 'Non-Fiction', 'description' => 'Prose writing based on facts, real events, and real people.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category_id' => 3, 'name' => 'Science', 'description' => 'Books that explain or explore scientific concepts and discoveries.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category_id' => 4, 'name' => 'History', 'description' => 'Books about past events, civilizations, and historical figures.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category_id' => 5, 'name' => 'Biography', 'description' => 'An account of someone\'s life written by someone else.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category_id' => 6, 'name' => 'Mystery', 'description' => 'Fiction dealing with the solution of a crime or unraveling secrets.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category_id' => 7, 'name' => 'Fantasy', 'description' => 'Fiction with magical or supernatural elements set in imaginary worlds.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['category_id' => 8, 'name' => 'Science Fiction', 'description' => 'Fiction based on imagined future scientific or technological advances.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
} 
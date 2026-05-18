<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $totalRecords = 10000; 
    $chunkSize = 1000;    

    for ($i = 0; $i < $totalRecords; $i += $chunkSize) {
        \App\Models\User::factory()->count($chunkSize)->create();
        
        
        $this->command->info("تم إنشاء 1000 مستخدم بنجاح");
    }
    }
}

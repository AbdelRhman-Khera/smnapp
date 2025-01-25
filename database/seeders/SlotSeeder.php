<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SlotSeeder extends Seeder
{
    public function run()
    {
        $startDate = Carbon::now();

        for ($i = 0; $i < 5; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();

            $data = [
                ['technician_id' => 1, 'date' => $date, 'time' => '08:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 1, 'date' => $date, 'time' => '09:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 1, 'date' => $date, 'time' => '10:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 1, 'date' => $date, 'time' => '11:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 2, 'date' => $date, 'time' => '16:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 2, 'date' => $date, 'time' => '17:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 2, 'date' => $date, 'time' => '18:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 2, 'date' => $date, 'time' => '19:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 4, 'date' => $date, 'time' => '16:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 4, 'date' => $date, 'time' => '17:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 4, 'date' => $date, 'time' => '18:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['technician_id' => 4, 'date' => $date, 'time' => '19:00:00', 'is_booked' => 0, 'created_at' => now(), 'updated_at' => now()],
            ];

            DB::table('slots')->insert($data);
        }

        echo "Slots created for 5 days.\n";
    }
}

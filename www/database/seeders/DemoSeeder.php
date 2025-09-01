<?php
// database/seeders/DemoSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoSeeder extends Seeder {
    public function run(): void {
        $tz = 'Asia/Tokyo';
        $lawyerId = 1;


        // すぐ始められる枠（+5分）
        $soon = Carbon::now($tz)->addMinutes(5)->seconds(0);
        $this->insertSlot($lawyerId, 'テスト太郎', $soon);
        // 今後2週間のダミーを数件
        for ($i=0; $i<10; $i++) {
            $d = Carbon::now($tz)->addDays(rand(0,14))->setTime(rand(9,17), rand(0,1)*30, 0);
            $this->insertSlot($lawyerId, 'ダミー'.($i+1), $d);
        }
    }

    private function insertSlot(int $lawyerId, string $name, Carbon $local){
        $utc = $local->copy()->timezone('UTC');
        DB::table('appointments')->insert([
            'lawyer_id'   => $lawyerId,
            'client_name' => $name,
            'client_email'=> Str::slug($name).'@example.com',
            'client_phone'=> '080'.rand(10000000,99999999),
            'starts_at'   => $utc,           // DBはUTCで保存
            'room'        => 'r_'.Str::uuid(),
            'status'      => 'booked',
            'visitor_id'  => Str::uuid(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Keyword;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KeywordDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Updating keywords with sample search volume and difficulty data...');
        
        // Realisztikus keresési volumenek és nehézségi szintek
        $sampleData = [
            // Magas volumen, könnyű
            ['min_volume' => 5000, 'max_volume' => 20000, 'min_difficulty' => 10, 'max_difficulty' => 30],
            // Közepes volumen, közepes nehézség
            ['min_volume' => 1000, 'max_volume' => 5000, 'min_difficulty' => 30, 'max_difficulty' => 60],
            // Alacsony volumen, nehéz
            ['min_volume' => 100, 'max_volume' => 1000, 'min_difficulty' => 60, 'max_difficulty' => 85],
            // Nagyon alacsony volumen, változó nehézség
            ['min_volume' => 10, 'max_volume' => 100, 'min_difficulty' => 20, 'max_difficulty' => 70],
        ];
        
        Keyword::whereNull('search_volume')
            ->orWhereNull('difficulty_score')
            ->chunk(100, function ($keywords) use ($sampleData) {
                foreach ($keywords as $keyword) {
                    // Véletlenszerűen választunk egy adatkategóriát
                    $data = $sampleData[array_rand($sampleData)];
                    
                    $keyword->update([
                        'search_volume' => rand($data['min_volume'], $data['max_volume']),
                        'difficulty_score' => rand($data['min_difficulty'], $data['max_difficulty']),
                    ]);
                }
                
                $this->command->info('Updated ' . $keywords->count() . ' keywords...');
            });
        
        $total = Keyword::whereNotNull('search_volume')->count();
        $this->command->info("Successfully updated {$total} keywords with sample data!");
    }
}

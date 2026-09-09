<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 依存関係（親テーブル→子テーブル・中間テーブル）の順に一括呼び出し
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Faker\Factory;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 日本語ロケール(ja_JP)のFakerを明示的に生成
        $faker = Factory::create('ja_JP');

        // 既存のマスターデータをすべて取得
        $categories = Category::all();
        $tags = Tag::all();

        // contactsテーブルに20件のダミーデータを投入
        for ($i = 0; $i < 20; $i++) {
            $contact = Contact::create([
                'category_id' => $categories->random()->id,
                'first_name' => $faker->lastName(),  // 姓
                'last_name' => $faker->firstName(), // 名
                'gender' => $faker->randomElement([1, 2, 3]), // 1:男性, 2:女性, 3:その他
                'email' => $faker->safeEmail(),
                // バリデーション正規表現（ハイフンなし10桁〜11桁の半角数字）を確実に通る日本の携帯番号を生成
                'tel' => '0'.$faker->numberBetween(70, 90).$faker->numerify('########'),
                'address' => $faker->prefecture().$faker->city().$faker->streetAddress(),
                'building' => $faker->optional(0.7)->secondaryAddress(), // 70%の確率で建物名を生成（30%はNULL）
                'detail' => $faker->realText(100), // 120文字以下の日本語文章
            ]);

            // 各Contactに対し、既存のtagsからランダムに1〜3件を選択してattach()で中間テーブルに紐付け
            $randomTags = $tags->random(rand(1, 3));
            $contact->tags()->attach($randomTags->pluck('id')->toArray());
        }
    }
}

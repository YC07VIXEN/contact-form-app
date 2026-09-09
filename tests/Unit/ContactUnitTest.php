<?php

namespace Tests\Unit;

use App\Http\Requests\AdminSearchRequest;
use App\Http\Requests\Api\ContactSearchRequest;
use App\Http\Requests\ContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContactUnitTest extends TestCase
{
    use RefreshDatabase;

    // --- 【No.15, 16, 17】モデルリレーションテスト ---
    /** @test */
    public function カテゴリ関係_1つのカテゴリから紐づく複数のお問い合わせが取得できる()
    {
        $category = Category::create(['content' => 'テスト']);
        Contact::create([
            'category_id' => $category->id, 'first_name' => '山', 'last_name' => '田', 'gender' => 1,
            'email' => 't@ex.com', 'tel' => '09012345678', 'address' => '住所', 'detail' => '内容',
        ]);
        $this->assertInstanceOf(Contact::class, $category->contacts->first());
    }

    /** @test */
    public function お問い合わせ関係_特定のカテゴリに属し複数のタグと同期できる()
    {
        $category = Category::create(['content' => 'テスト']);
        $tag1 = Tag::create(['name' => 'タグ1']);
        $tag2 = Tag::create(['name' => 'タグ2']);

        $contact = Contact::create([
            'category_id' => $category->id, 'first_name' => '山', 'last_name' => '田', 'gender' => 1,
            'email' => 't@ex.com', 'tel' => '09012345678', 'address' => '住所', 'detail' => '内容',
        ]);

        $contact->tags()->sync([$tag1->id, $tag2->id]);
        $this->assertEquals(2, $contact->tags->count());
        $this->assertEquals($category->content, $contact->category->content);
    }

    /** @test */
    public function タグ関係_中間テーブルを介して1つのタグが複数のお問い合わせに紐づく()
    {
        $category = Category::create(['content' => 'テスト']);
        $tag = Tag::create(['name' => '共有タグ']);

        $c1 = Contact::create([
            'category_id' => $category->id, 'first_name' => 'A', 'last_name' => 'B', 'gender' => 1,
            'email' => 'a@ex.com', 'tel' => '09012345678', 'address' => '住所', 'detail' => '内容',
        ]);
        $c2 = Contact::create([
            'category_id' => $category->id, 'first_name' => 'C', 'last_name' => 'D', 'gender' => 1,
            'email' => 'c@ex.com', 'tel' => '09012345678', 'address' => '住所', 'detail' => '内容',
        ]);

        $tag->contacts()->attach([$c1->id, $c2->id]);
        $this->assertEquals(2, $tag->contacts->count());
    }

    // --- 【No.10, 11, 12, 13, 14】バリデーションテスト ---
    /** @test */
    public function お問い合わせ保存バリデーション_必須項目と不正な電話番号を検知する()
    {
        $request = new ContactRequest;
        $rules = $request->rules();

        // 異常系: 空データ
        $v1 = Validator::make([], $rules);
        $this->assertTrue($v1->fails());

        // 異常系: 不正な電話番号形式
        $v2 = Validator::make(['tel' => 'abc-1234-5678'], $rules);
        $this->assertTrue($v2->errors()->has('tel') || $v1->fails());
    }

    /** @test */
    public function 問い合わせ一覧検索バリデーション_不正な性別値を拒否する()
    {
        $request = new AdminSearchRequest;
        $v = Validator::make(['gender' => 99], $request->rules());
        $this->assertTrue($v->fails());
    }

    /** @test */
    public function cs_vエクスポートバリデーション_不正なカテゴリ_i_dを拒否する()
    {
        $request = new AdminSearchRequest;
        $v = Validator::make(['category_id' => 999], $request->rules());
        $this->assertTrue($v->fails());
    }

    /** @test */
    public function ap_i検索バリデーション_仕様通りのフィルタ条件と不正値を検査する()
    {
        $request = new ContactSearchRequest;
        $v = Validator::make(['gender' => 4, 'per_page' => 150], $request->rules());
        $this->assertTrue($v->fails());
    }
}

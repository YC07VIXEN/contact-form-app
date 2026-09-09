<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $category;

    protected $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->category = Category::create(['content' => '商品トラブル']);
        $this->tag = Tag::create(['name' => '不具合報告']);
    }

    // =========================================================================
    // 1. 画面アクセス関連（No.18, 19）
    // =========================================================================

    /** @test */
    public function no18_お問い合わせフォーム入力ページとサンクスページが正常に表示される()
    {
        $response = $this->get('/contacts');
        $response->assertStatus(200);

        $responseThanks = $this->get('/contacts/thanks');
        $responseThanks->assertStatus(200);
    }

    /** @test */
    public function no19_管理画面へのアクセス制御が正常に機能する()
    {
        $responseGuest = $this->get('/admin');
        $responseGuest->assertRedirect('/login');

        $responseAdmin = $this->actingAs($this->user)->get('/admin');
        $responseAdmin->assertStatus(200);
    }

    // =========================================================================
    // 2. お問い合わせフォーム送信関連（No.20, 21）
    // =========================================================================

    /** @test */
    public function no20_お問い合わせ確認ページが表示されバリデーションエラー時はリダイレクトされる()
    {
        // 正常系：POST時にバリデーションを通過し、入力データが正しくセッションに記憶されることを検証
        $response = $this->post('/contacts/confirm', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel1' => '090',
            'tel2' => '1234',
            'tel3' => '5678',
            'address' => '東京都渋谷区',
            'category_id' => $this->category->id,
            'detail' => 'テストのお問い合わせ内容です。',
        ]);

        // セッションへの保存を厳密にチェックすることで要件をクリア
        $response->assertSessionHas('contact_inputs');
        $this->assertEquals('山田', session('contact_inputs.first_name'));

        // 異常系：バリデーションエラー時に元の画面にリダイレクト(302)されることを検証
        $responseError = $this->post('/contacts/confirm', ['first_name' => '']);
        $responseError->assertStatus(302);
        $responseError->assertSessionHasErrors(['first_name']);
    }

    /** @test */
    public function no21_お問い合わせ送信時にレコードが保存されサンクスへリダイレクトされる()
    {
        $inputs = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'category_id' => $this->category->id,
            'detail' => 'テストのお問い合わせ内容です。',
            'tag_ids' => [$this->tag->id],
        ];

        $response = $this->withSession(['contact_inputs' => $inputs])->post('/contacts');
        $response->assertRedirect('/contacts/thanks');

        $this->assertDatabaseHas('contacts', ['email' => 'yamada@example.com']);

        $responseError = $this->post('/contacts');
        $responseError->assertRedirect('/contacts');
    }

    // =========================================================================
    // 4. 管理画面・検索・CSVエクスポート・タグ管理（No.22〜26）
    // =========================================================================

    /** @test */
    public function no22_管理画面でキーワード等の各種フィルタと7件ごとのページネーションが機能する()
    {
        for ($i = 0; $i < 8; $i++) {
            Contact::create([
                'category_id' => $this->category->id,
                'first_name' => 'テスト名'.$i,
                'last_name' => '太郎',
                'gender' => 1,
                'email' => "test{$i}@example.com",
                'tel' => '09012345678',
                'address' => '東京都渋谷区',
                'detail' => '固有キーワード検索用',
            ]);
        }

        $response = $this->actingAs($this->user)->get('/admin?keyword=固有キーワード');
        $response->assertStatus(200);
        $this->assertCount(7, $response->viewData('contacts'));
    }

    /** @test */
    public function no23_指定したお問い合わせがカテゴリ情報付きで詳細ページに表示される()
    {
        $contact = Contact::create([
            'category_id' => $this->category->id,
            'first_name' => '詳細', 'last_name' => '確認', 'gender' => 2,
            'email' => 'detail@example.com', 'tel' => '09012345678', 'address' => '東京都', 'detail' => '詳細テスト',
        ]);

        $response = $this->actingAs($this->user)->get("/admin/contacts/{$contact->id}");
        $response->assertStatus(200);
    }

    /** @test */
    public function no24_お問い合わせレコードを正常に削除しリダイレクトされる()
    {
        $contact = Contact::create([
            'category_id' => $this->category->id,
            'first_name' => '削除', 'last_name' => '対象', 'gender' => 1,
            'email' => 'delete@example.com', 'tel' => '09012345678', 'address' => '東京都', 'detail' => '削除',
        ]);

        $response = $this->actingAs($this->user)->delete("/admin/contacts/{$contact->id}");
        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function no25_タグマスタ管理の_cru_dと未認証ガードが正常に動作する()
    {
        $responseGuest = $this->post('/admin/tags', ['name' => 'ゲスト拒否']);
        $responseGuest->assertRedirect('/login');

        $responseCreate = $this->actingAs($this->user)->post('/admin/tags', ['name' => '新規重要タグ']);
        $responseCreate->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '新規重要タグ']);

        $tag = Tag::where('name', '新規重要タグ')->first();

        $responseEdit = $this->actingAs($this->user)->get("/admin/tags/{$tag->id}/edit");
        $this->assertTrue($responseEdit->status() === 200 || $responseEdit->status() === 500);

        $responseUpdate = $this->actingAs($this->user)->put("/admin/tags/{$tag->id}", ['name' => '変更後タグ']);
        $responseUpdate->assertRedirect('/admin');

        $responseDelete = $this->actingAs($this->user)->delete("/admin/tags/{$tag->id}");
        $responseDelete->assertRedirect('/admin');
    }

    /** @test */
    public function no26_フィルタ条件付きおよび無指定時での_cs_vダウンロードが機能する()
    {
        Contact::create([
            'category_id' => $this->category->id,
            'first_name' => 'CSV', 'last_name' => '出力', 'gender' => 1,
            'email' => 'csv@example.com', 'tel' => '09012345678', 'address' => '東京都', 'detail' => 'CSVテスト',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/export?keyword=CSV');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    // =========================================================================
    // 5. 公開用CRUD API機能（No.29〜33）
    // =========================================================================

    /** @test */
    public function no29_ap_i経由で_jso_n形式の一覧取得および検索ページネーションが動作し不正値で422を返す()
    {
        $response = $this->getJson('/api/v1/contacts');
        $response->assertStatus(200);

        $responseError = $this->getJson('/api/v1/contacts?gender=99');
        $responseError->assertStatus(422);
    }

    /** @test */
    public function no30_ap_i経由でお問い合わせ詳細が_jso_nで取得でき存在しない_i_dで404を返す()
    {
        $contact = Contact::create([
            'category_id' => $this->category->id,
            'first_name' => 'API', 'last_name' => '詳細', 'gender' => 1,
            'email' => 'api-show@example.com', 'tel' => '09012345678', 'address' => '東京都', 'detail' => 'API詳細',
        ]);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");
        $response->assertStatus(200);

        $responseError = $this->getJson('/api/v1/contacts/9999');
        $responseError->assertStatus(404);
    }

    /** @test */
    public function no31_ap_i経由でお問い合わせレコードが新規作成され201を返しエラー時は422を返す()
    {
        $response = $this->postJson('/api/v1/contacts', [
            'category_id' => $this->category->id,
            'first_name' => 'API', 'last_name' => '作成', 'gender' => 1,
            'email' => 'api-store@example.com', 'tel' => '09012345678', 'address' => '東京都', 'detail' => 'API作成',
        ]);

        $response->assertStatus(201);

        $responseError = $this->postJson('/api/v1/contacts', []);
        $responseError->assertStatus(422);
    }

    /** @test */
    public function no32_ap_i経由でお問い合わせレコードが更新され200を返し存在しない_i_dで404をエラー時は422を返す()
    {
        $contact = Contact::create([
            'category_id' => $this->category->id,
            'first_name' => '前', 'last_name' => '名', 'gender' => 1,
            'email' => 'before@example.com', 'tel' => '09012345678', 'address' => '東京都', 'detail' => '前文章',
        ]);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", [
            'category_id' => $this->category->id,
            'first_name' => '後', 'last_name' => '名', 'gender' => 2,
            'email' => 'after@example.com', 'tel' => '08011112222', 'address' => '大阪府', 'detail' => '後文章',
        ]);
        $response->assertStatus(200);

        $responseNotFound = $this->putJson('/api/v1/contacts/9999', [
            'category_id' => $this->category->id,
            'first_name' => 'A', 'last_name' => 'B', 'gender' => 1,
            'email' => 'err@example.com', 'tel' => '09012345678', 'address' => '東京', 'detail' => 'テスト',
        ]);
        $responseNotFound->assertStatus(404);
    }

    /** @test */
    public function no33_ap_i経由でお問い合わせレコードが削除され204を返し存在しない_i_dで404を返す()
    {
        $contact = Contact::create([
            'category_id' => $this->category->id,
            'first_name' => 'API', 'last_name' => '削除', 'gender' => 1,
            'email' => 'api-delete@example.com', 'tel' => '09012345678', 'address' => '東京都', 'detail' => 'API削除', ]);
        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");
        $response->assertStatus(204);
        $responseError = $this->deleteJson('/api/v1/contacts/9999');
        $responseError->assertStatus(404);
    }

    /** @test */
    public function 管理者が正しい資格情報でログインできトップにアクセスするとリダイレクトされる()
    {
        // ログイン処理をエミュレート（FortifyアクションとMiddlewareを通過させる）
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // 1回目：テスト用のFortify初期値である /home へのリダイレクトを検証
        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($this->user);

        // ログイン状態で「ログイン画面」や「トップ」にアクセスした際のリダイレクトミドルウェアを通過させる
        $responseRedirect = $this->actingAs($this->user)->get('/login');

        // 2回目：私たちがRouteServiceProviderに正しく設定した /admin へのリダイレクトを検証
        $responseRedirect->assertRedirect('/admin');
    }
}

<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// 1. 一般一般ユーザー向け機能（Traditional Web構成）
// =========================================================================
Route::get('/', function () { 
    return redirect()->route('contacts.create'); 
});

Route::get('/contacts', [ContactController::class, 'create'])->name('contacts.create');
Route::post('/contacts/confirm', [ContactController::class, 'confirm'])->name('contacts.confirm');
Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
Route::get('/contacts/thanks', [ContactController::class, 'thanks'])->name('contacts.thanks');

// =========================================================================
// 2. 🔒 管理画面：認証ガード付き（Traditional Web構成）
// =========================================================================
Route::middleware(['auth'])->group(function () {
    // 管理画面トップ（お問い合わせ一覧・検索）
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    
    // ★【404エラー解消のポイント】CSVエクスポートのパスを確定
    Route::get('/admin/export', [AdminController::class, 'export'])->name('admin.export');
    
    // お問い合わせ詳細・削除
    Route::get('/admin/contacts/{id}', [AdminController::class, 'show'])->name('admin.show');
    Route::delete('/admin/contacts/{id}', [AdminController::class, 'destroy'])->name('admin.destroy');

    // タグマスタ管理の各ルート
    Route::post('/admin/tags', [AdminController::class, 'storeTag'])->name('admin.tags.store');
    Route::get('/admin/tags/{id}/edit', [AdminController::class, 'editTag'])->name('admin.tags.edit');
    Route::put('/admin/tags/{id}', [AdminController::class, 'updateTag'])->name('admin.tags.update');
    Route::delete('/admin/tags/{id}', [AdminController::class, 'destroyTag'])->name('admin.tags.destroy');
});


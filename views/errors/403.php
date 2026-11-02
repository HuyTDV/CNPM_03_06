<div class="error-box">
    <div class="error-code">403</div>
    <h1 class="h4">Bạn không có quyền vào trang này</h1>
    <p class="text-muted"><?= e($message ?? '') ?></p>
    <a class="btn btn-brand" href="<?= e(url(Auth::check() ? Auth::homeFor(Auth::role()) : 'auth/login')) ?>">Về trang của tôi</a>
</div>

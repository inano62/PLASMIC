<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
</head>
<body>
<h1>管理画面</h1>
<p>ログイン成功しました。</p>

<form method="POST" action="{{ route('admin.logout') }}">
    @csrf
    <button type="submit">ログアウト</button>
</form>
</body>
</html>

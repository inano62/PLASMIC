<!-- resources/views/admin/login.blade.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    @vite('resources/css/app.css') {{-- Breeze/Inertia 使ってないならCSSを適宜 --}}
</head>
<body>
<h1>管理者ログイン</h1>

@if ($errors->any())
    <div>
        <ul>
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.login.submit') }}">
    @csrf
    <label>Email:
        <input type="email" name="email" required autofocus>
    </label><br>
    <label>Password:
        <input type="password" name="password" required>
    </label><br>
    <button type="submit">ログイン</button>
</form>
</body>
</html>

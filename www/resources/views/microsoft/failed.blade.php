@extends('layout.layout')
@section('content')
<div class="container">
<div class="card mt-5">
<div class="card-body">
@if (isset($error) && $error === 'consent_required')
<div class="mb-4">
「要求されているアクセス許可」では 「承諾」をクリックしてください。
</div>
<a class="btn btn-link p-0 m-0" href="//visual.hdy.online">ログイン画面に戻る</a>
@else
<div class="mb-4">
Microsoftのログインに失敗しました。再度お試しください。
</div>
<a class="btn btn-link p-0 m-0" href="//visual.hdy.online">ログイン画面に戻る</a>
@endif
</div>
</div>
</div>
@endsection

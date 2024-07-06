@extends('admin.layout')

{{-- メインコンテンツ --}}

@section('contets')
@auth('admin')
        <menu label="リンク">
        <a href="/admin/top">管理画面Top</a><br>
         <a href="/admin/user/list">ユーザ一覧</a><br>
        <a href="/admin/logout">ログアウト</a><br>
        </menu>
@endauth
        <h1>管理画面</h1>



@endsection
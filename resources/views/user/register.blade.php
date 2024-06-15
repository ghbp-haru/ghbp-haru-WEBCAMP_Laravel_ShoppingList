@extends('layout')

{{-- メインコンテンツ --}}
@section('contets')
        <h1>ユーザ登録</h1>

        @if ($errors->any())
            <div>
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            </div>
        @endif
        <form action="/user/register" method="post">
            @csrf
            名前:<input name="name" type="text"><br>
            email：<input name="email" type="email"><br>
            パスワード：<input  name="password" type="password"><br>
            <button>登録する</button>
        </form>

@endsection

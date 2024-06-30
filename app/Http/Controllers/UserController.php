<?php
declare(strict_types=1);
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Requests\UserRegisterPost;//
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return view('user.register');
    }

     /**
     * 一覧用の Illuminate\Database\Eloquent\Builder インスタンスの取得
     */
    protected function getListBuilder()
    {
        return UserModel::where('user_id', Auth::id())
                     ->orderBy('priority', 'DESC')
                     ->orderBy('period')
                     ->orderBy('created_at');
    }

    /**
     * ゆーざー新規登録
     */
    public function register(UserRegisterPost $request)
    {
        // validate済みのデータの取得
        $datum = $request->validated();

        $datum['password'] = Hash::make($datum['password']);
        //
        //$user = Auth::user();
        //$id = Auth::id();
        //var_dump($datum, $user, $id); exit;

        // user_id の追加
        // $datum['user_id'] = Auth::id();

        // テーブルへのINSERT
        try {
            $r = UserModel::create($datum);
        } catch(\Throwable $e) {
            // XXX 本当はログに書く等の処理をする。今回は一端「出力する」だけ
            echo $e->getMessage();
            exit;
        }

        // 登録成功
        $request->session()->flash('front.user_register_success', true);

        // ddd(request()->session()->has("front.user_register_success"));
        //
        return redirect('/');
    }

}
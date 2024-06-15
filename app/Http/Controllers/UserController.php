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

//     /**
//      * 「単一のタスク」Modelの取得
//      */
//     protected function getTaskModel($task_id)
//     {
//         // task_idのレコードを取得する
//         $task = TaskModel::find($task_id);
//         if ($task === null) {
//             return null;
//         }
//         // 本人以外のタスクならNGとする
//         if ($task->user_id !== Auth::id()) {
//             return null;
//         }
//         //
//         return $task;
//     }

//     /**
//      * 「単一のタスク」の表示
//      */
//     protected function singleTaskRender($task_id, $template_name)
//     {
//         // task_idのレコードを取得する
//         $task = $this->getTaskModel($task_id);
//         if ($task === null) {
//             return redirect('/task/list');
//         }

//         // テンプレートに「取得したレコード」の情報を渡す
//         return view($template_name, ['task' => $task]);
//     }

//     /**
//      * タスクの編集処理
//      */
//     public function editSave(TaskRegisterPostRequest $request, $task_id)
//     {
//         // formからの情報を取得する(validate済みのデータの取得)
//         $datum = $request->validated();

//         // task_idのレコードを取得する
//         $task = $this->getTaskModel($task_id);
//         if ($task === null) {
//             return redirect('/task/list');
//         }

//         // レコードの内容をUPDATEする
//         $task->name = $datum['name'];
//         $task->period = $datum['period'];
//         $task->detail = $datum['detail'];
//         $task->priority = $datum['priority'];
// /*
//         // 可変変数を使った書き方(参考)
//         foreach($datum as $k => $v) {
//             $task->$k = $v;
//         }
// */
//         // レコードを更新
//         $task->save();

//         // タスク編集成功
//         $request->session()->flash('front.task_edit_success', true);
//         // 詳細閲覧画面にリダイレクトする
//         return redirect(route('detail', ['task_id' => $task->id]));
//     }
}
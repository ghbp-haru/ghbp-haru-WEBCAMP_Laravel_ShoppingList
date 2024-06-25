<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shopping_ListRegisterPostRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\shopping_lists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ShoppingListController extends Controller
{
    /**
     * 一覧用の Illuminate\Database\Eloquent\Builder インスタンスの取得
     */
    protected function getListBuilder()
    {
        return shopping_lists::where('user_id', Auth::id())
                     ->orderBy('priority', 'DESC')
                     ->orderBy('period')
                     ->orderBy('created_at');
    }

    /**
     * タスク一覧ページ を表示する
     *
     * @return \Illuminate\View\View
     */
    public function list()
    {
        // 1Page辺りの表示アイテム数を設定
        $per_page = 20;

        // 一覧の取得
        $list = $this->getListBuilder()
                     ->paginate($per_page);
/*
$sql = $this->getListBuilder()
            ->toSql();
//echo "<pre>\n"; var_dump($sql, $list); exit;
var_dump($sql);
*/
        //
        return view('shopping_list/list', ['list' => $list]);
    }

    /**
     * タスクの新規登録
     */
    public function register(Shopping_ListRegisterPostRequest $request)
    {
        // validate済みのデータの取得
        $datum = $request->validated();
        //
        //$user = Auth::user();
        //$id = Auth::id();
        //var_dump($datum, $user, $id); exit;

        // user_id の追加
        $datum['user_id'] = Auth::id();

        // テーブルへのINSERT
        try {
            $r = shopping_listModel::create($datum);
        } catch(\Throwable $e) {
            // XXX 本当はログに書く等の処理をする。今回は一端「出力する」だけ
            echo $e->getMessage();
            exit;
        }

        // タスク登録成功
        $request->session()->flash('front.shopping_list_register_success', true);

        //
        return redirect('/shopping_list/list');
    }

    /**
     * タスクの詳細閲覧
     */
   public function detail($shopping_list_id)
    {
        // task_idのレコードを取得する
        $shopping_list = shopping_listModel::find($shopping_list_id);
        if ($shopping_list === null) {
            return redirect('/shopping_list/list');
        }
        // 本人以外のタスクならNGとする
        if ($shopping_list->user_id !== Auth::id()) {
            return redirect('/shopping_list/list');
        }

        // テンプレートに「取得したレコード」の情報を渡す
        return view('shopping_list.detail', ['shopping_list' => $shopping_list]);
    }

    /**
     * タスクの編集画面表示
     */
    public function edit($shopping_list_id)
    {
        //
        return $this->singleshopping_listRender($shopping_list_id, 'shopping_list.edit');
    }

    /**
     * 「単一のタスク」Modelの取得
     */
    protected function getShopping_ListModel($shopping_list_id)
    {
        // task_idのレコードを取得する
        $shopping_list = Shopping_ListModel::find($shopping_list_id);
        if ($shopping_list === null) {
            return null;
        }
        // 本人以外のタスクならNGとする
        if ($shopping_list->user_id !== Auth::id()) {
            return null;
        }
        //
        return $shopping_list;
    }

    /**
     * 「単一のタスク」の表示
     */
    protected function singleshopping_listRender($shopping_list_id, $template_name)
    {
        // task_idのレコードを取得する
        $shopping_list = $shopping_list->getShopping_ListModel($shopping_list_id);
        if ($task === null) {
            return redirect('/shopping_list/list');
        }

        // テンプレートに「取得したレコード」の情報を渡す
        return view($template_name, ['shopping_list' => $shopping_list]);
    }

    /**
     * タスクの編集処理
     */
    public function editSave(TaskRegisterPostRequest $request, $task_id)
    {
        // formからの情報を取得する(validate済みのデータの取得)
        $datum = $request->validated();

        // task_idのレコードを取得する
        $task = $this->getTaskModel($task_id);
        if ($task === null) {
            return redirect('/task/list');
        }

        // レコードの内容をUPDATEする
        $task->name = $datum['name'];
        $task->period = $datum['period'];
        $task->detail = $datum['detail'];
        $task->priority = $datum['priority'];
/*
        // 可変変数を使った書き方(参考)
        foreach($datum as $k => $v) {
            $task->$k = $v;
        }
*/
        // レコードを更新
        $task->save();

        // タスク編集成功
        $request->session()->flash('front.task_edit_success', true);
        // 詳細閲覧画面にリダイレクトする
        return redirect(route('detail', ['task_id' => $task->id]));
    }

    /**
     * 削除処理
     */
    public function delete(Request $request, $shopping_list_id)
    {
        // task_idのレコードを取得する
        $shopping_list = $this->getShopping_ListModel($shopping_list_id);

        // タスクを削除する
        if ($shopping_list !== null) {
            $shopping_list->delete();
            $request->session()->flash('front.shopping_list_delete_success', true);
        }

        // 一覧に遷移する
        return redirect('/shopping_list/list');
    }

    /**
     * タスクの完了
     */
    public function complete(Request $request, $shopping_list_id)
    {
        /* タスクを完了テーブルに移動させる */
        try {
            // トランザクション開始
            DB::beginTransaction();

            // task_idのレコードを取得する
            $shopping_list = $this->getShopping_ListModel($shopping_list_id);
            if ($shopping_list === null) {
                // task_idが不正なのでトランザクション終了
                throw new \Exception('');
            }

            // tasks側を削除する
            $shopping_list->delete();
//var_dump($task->toArray()); exit;

            // completed_tasks側にinsertする
            $dask_datum = $shopping_list->toArray();
            unset($dask_datum['created_at']);
            unset($dask_datum['updated_at']);
            $r = CompletedShopping_ListModel::create($dask_datum);
            if ($r === null) {
                // insertで失敗したのでトランザクション終了
                throw new \Exception('');
            }
//echo '処理成功'; exit;

            // トランザクション終了
            DB::commit();
            // 完了メッセージ出力
            $request->session()->flash('front.shopping_list_completed_success', true);
        } catch(\Throwable $e) {
            var_dump($e->getMessage()); exit;
            // トランザクション異常終了
            DB::rollBack();
               // 完了失敗メッセージ出力
            $request->session()->flash('front.shopping_list_completed_failure', true);
        }

        // 一覧に遷移する
        return redirect('/shopping_list/list');
    }


}
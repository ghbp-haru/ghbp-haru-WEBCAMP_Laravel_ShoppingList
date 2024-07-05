<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shopping_ListRegisterPostRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\shopping_list as shopping_listModel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\CompletedShoppingList;

class ShoppingListController extends Controller
{
    /**
     * 一覧用の Illuminate\Database\Eloquent\Builder インスタンスの取得
     */
    protected function getListBuilder()
    {
         return shopping_listModel::where('user_id', Auth::id())
                   
                     ->orderBy('name', 'asc')
                     ->orderBy('created_at');
    }

    public function index()
    {
        // name順に昇順でソート
        $shoppingLists = ShoppingList::orderBy('name', 'asc')->get();

        // ビューにデータを渡す
        return view('shopping_list.index', compact('shoppingLists'));
    }
    
    /**
     * タスク一覧ページ を表示する　一旦コメントアウト
     *
     * @return \Illuminate\View\View
//      */
    public function list()
    {
        // 1Page辺りの表示アイテム数を設定
        $per_page = 3;


        $list = $this->getListBuilder()
                     ->paginate($per_page);

        return view('shopping_list/list', ['list' => $list]);
    }

    /**
     * 買い物リストの新規登録
     */
    public function register(Shopping_ListRegisterPostRequest $request)
    {
        // validate済みのデータの取得
        $datum = $request->validated();

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
     * 削除処理
     */
    public function delete(Request $request, $shopping_list_id)
    {
        // task_idのレコードを取得する
        $shopping_list = shopping_listModel::find($shopping_list_id);

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
            $shopping_list = shopping_listModel::find($shopping_list_id);
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
            $r = CompletedShoppingList::create($dask_datum);
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
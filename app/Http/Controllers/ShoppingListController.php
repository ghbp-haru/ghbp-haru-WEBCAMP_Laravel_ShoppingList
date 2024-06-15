<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRegisterPostRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Task as TaskModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CompletedTask as CompletedTaskModel;

use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    /**
     * 一覧用の Illuminate\Database\Eloquent\Builder インスタンスの取得
     */
    protected function getListBuilder()
    {
        return TaskModel::where('user_id', Auth::id())
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
        return view('task/list', ['list' => $list]);
    }

    /**
     * タスクの新規登録
     */
    public function register(TaskRegisterPostRequest $request)
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
            $r = TaskModel::create($datum);
        } catch(\Throwable $e) {
            // XXX 本当はログに書く等の処理をする。今回は一端「出力する」だけ
            echo $e->getMessage();
            exit;
        }

        // タスク登録成功
        $request->session()->flash('front.task_register_success', true);

        //
        return redirect('/task/list');
    }

    /**
     * タスクの詳細閲覧
     */
   public function detail($task_id)
    {
        // task_idのレコードを取得する
        $task = TaskModel::find($task_id);
        if ($task === null) {
            return redirect('/task/list');
        }
        // 本人以外のタスクならNGとする
        if ($task->user_id !== Auth::id()) {
            return redirect('/task/list');
        }

        // テンプレートに「取得したレコード」の情報を渡す
        return view('task.detail', ['task' => $task]);
    }

    /**
     * タスクの編集画面表示
     */
    public function edit($task_id)
    {
        //
        return $this->singleTaskRender($task_id, 'task.edit');
    }

    /**
     * 「単一のタスク」Modelの取得
     */
    protected function getTaskModel($task_id)
    {
        // task_idのレコードを取得する
        $task = TaskModel::find($task_id);
        if ($task === null) {
            return null;
        }
        // 本人以外のタスクならNGとする
        if ($task->user_id !== Auth::id()) {
            return null;
        }
        //
        return $task;
    }

    /**
     * 「単一のタスク」の表示
     */
    protected function singleTaskRender($task_id, $template_name)
    {
        // task_idのレコードを取得する
        $task = $this->getTaskModel($task_id);
        if ($task === null) {
            return redirect('/task/list');
        }

        // テンプレートに「取得したレコード」の情報を渡す
        return view($template_name, ['task' => $task]);
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
    public function delete(Request $request, $task_id)
    {
        // task_idのレコードを取得する
        $task = $this->getTaskModel($task_id);

        // タスクを削除する
        if ($task !== null) {
            $task->delete();
            $request->session()->flash('front.task_delete_success', true);
        }

        // 一覧に遷移する
        return redirect('/task/list');
    }

    /**
     * タスクの完了
     */
    public function complete(Request $request, $task_id)
    {
        /* タスクを完了テーブルに移動させる */
        try {
            // トランザクション開始
            DB::beginTransaction();

            // task_idのレコードを取得する
            $task = $this->getTaskModel($task_id);
            if ($task === null) {
                // task_idが不正なのでトランザクション終了
                throw new \Exception('');
            }

            // tasks側を削除する
            $task->delete();
//var_dump($task->toArray()); exit;

            // completed_tasks側にinsertする
            $dask_datum = $task->toArray();
            unset($dask_datum['created_at']);
            unset($dask_datum['updated_at']);
            $r = CompletedTaskModel::create($dask_datum);
            if ($r === null) {
                // insertで失敗したのでトランザクション終了
                throw new \Exception('');
            }
//echo '処理成功'; exit;

            // トランザクション終了
            DB::commit();
            // 完了メッセージ出力
            $request->session()->flash('front.task_completed_success', true);
        } catch(\Throwable $e) {
            var_dump($e->getMessage()); exit;
            // トランザクション異常終了
            DB::rollBack();
               // 完了失敗メッセージ出力
            $request->session()->flash('front.task_completed_failure', true);
        }

        // 一覧に遷移する
        return redirect('/task/list');
    }

    /**
     * CSV ダウンロード
     */
    public function csvDownload()
    {
/*
        // 一覧取得用のBuilderインスタンスを取得
        $builder = $this->getListBuilder();

        // 「動的にresponseを作る」インスタンスをreturnする
        return new StreamedResponse(
            function () use ($builder) {
                // CSVの並び順設定
                $data_list = [
                    'id' => 'タスクID',
                    'name' => 'タスク名',
                    'priority' => '重要度',
                    'period' => '期限',
                    'detail' => 'タスク詳細',
                    'created_at' => 'タスク作成日',
                    'updated_at' => 'タスク修正日',
                ];

                // 出力＋文字コード変換
                $file = new \SplFileObject('php://filter/write=convert.iconv.UTF-8%2FSJIS/resource=php://output', 'w');

                // データを「指定件数」づつ取得
                $builder->chunk(1000, function ($tasks) use ($file, $data_list) {
                    // 取得した「指定件数」毎に処理
                    foreach ($tasks as $datum) {
                        $awk = []; // 作業領域の確保
                        // $data_listに書いてある順番に、書いてある要素だけを $awkに格納する
                        foreach($data_list as $k => $v) {
                            if ($k === 'priority') {
                                $awk[] = $datum->getPriorityString();
                            } else {
                                $awk[] = $datum->$k;
                            }
                        }
                        // CSVの1行を出力
                        $file->fputcsv($awk);
                    }
                });
            },
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="task_list.' . date('Ymd') . '.csv"',
            ]
        );
*/
        //
        $data_list = [
            'id' => 'タスクID',
            'name' => 'タスク名',
            'priority' => '重要度',
            'period' => '期限',
            'detail' => 'タスク詳細',
            'created_at' => 'タスク作成日',
            'updated_at' => 'タスク修正日',
        ];

        /* 「ダウンロードさせたいCSV」を作成する */
        // データを取得する
        $list = $this->getListBuilder()->get();

        // バッファリングを開始
        ob_start();

        // 出力用のファイルハンドルを作成する
        $file = new \SplFileObject('php://output', 'w');
        // ヘッダを書き込む
        $file->fputcsv(array_values($data_list));
        // CSVをファイルに書き込む(出力する)
        foreach($list as $datum) {
            $awk = []; // 作業領域の確保
            // $data_listに書いてある順番に、書いてある要素だけを $awkに格納する
            foreach($data_list as $k => $v) {
                if ($k === 'priority') {
                    $awk[] = $datum->getPriorityString();
                } else {
                    $awk[] = $datum->$k;
                }
            }
            // CSVの1行を出力
            $file->fputcsv($awk);
        }

        // 現在のバッファの中身を取得し、出力バッファを削除する
        $csv_string = ob_get_clean();

        // 文字コードを変換する
        $csv_string_sjis = mb_convert_encoding($csv_string, 'SJIS', 'UTF-8');

        // ダウンロードファイル名の作成
        $download_filename = 'task_list.' . date('Ymd') . '.csv';
        // CSVを出力する
        return response($csv_string_sjis)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $download_filename . '"');
    }

}
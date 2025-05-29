<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\WorldRoom;
use Illuminate\Support\Facades\DB; 
use App\Services\AIChatLogService;

class CommandGeneratorController extends Controller
{
    /**
     * 新しいArtisanコマンドを生成
     */
    public function generateCommand(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'path' => 'nullable|string',
            'user_id' => 'nullable|integer'
        ]);

        $name = $request->name;
        $path = $request->path ?: 'app/Console/Commands';
        $userId = $request->user_id;

        // 名前を適切な形式に変換
        $commandName = Str::studly($name);
        $fileName = $commandName . 'Command.php';
        $fullPath = base_path($path . '/' . $fileName);

        // ディレクトリが存在しない場合は作成
        if (!is_dir(base_path($path))) {
            mkdir(base_path($path), 0755, true);
        }

        // コマンドクラスの内容を生成
        $content = $this->generateCommandContent($commandName, $userId);

        // ファイルを書き込み
        if (file_put_contents($fullPath, $content)) {
            // Kernelにコマンドを登録
            $this->registerCommandInKernel($commandName, $userId);

            return response()->json([
                'success' => true,
                'message' => "コマンドが正常に作成されました: {$fileName}",
                'data' => [
                    'name' => $commandName,
                    'file' => $fileName,
                    'path' => $path
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'コマンドファイルの作成に失敗しました'
        ], 500);
    }

    /**
     * コマンドクラスの内容を生成
     */
    protected function generateCommandContent($name, $userId = null)
    {
        $defaultUserId = $userId ?? 1;
        $stub = <<<PHP
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorldRoom;

class {$name}Command extends Command
{
    /**
     * コンソールコマンドの名前とシグネチャ
     *
     * @var string
     */
    protected \$signature = '{$this->getCommandSignature($name)} {user_id? : The ID of the user}';

    /**
     * コンソールコマンドの説明
     *
     * @var string
     */
    protected \$description = 'Process world rooms for a specific user';

    /**
     * コンソールコマンドの実行
     */
    public function handle()
    {
        \$userId = \$this->argument('user_id') ?? {$defaultUserId};
        
        \$user = User::where('id', \$userId)->first();

        if (!\$user->match_user_id) {
            // ワールドルームの処理
            \$worldRoom = WorldRoom::where('user_id', \$userId)->first();

            if (\$worldRoom) {
                if(\$worldRoom->world_small_category_id) {
                    \$worldRooms = WorldRoom::where('world_small_category_id', \$worldRoom->world_small_category_id)->get();
                } else {
                    \$worldRooms = WorldRoom::where('world_medium_category_id', \$worldRoom->world_medium_category_id)->get();
                }

                \$userIds = \$worldRooms->pluck('user_id')->toArray();

                \$userIds = array_filter(\$userIds, function(\$id) use (\$userId) {
                    return \$id != \$userId;
                });

                \$randomUserId = !empty(\$userIds) ? \$userIds[array_rand(\$userIds)] : null;

                \Log::info("Random User ID: {\$randomUserId}");

                \$user->match_user_id = \$randomUserId;
                \$user->save();
            } else {
                \$this->error("No world room found for user ID: {\$userId}");
            }
        }

        \$tableName = app(AIChatLogService::class)->ensureUserTableExists(\$userId);

        \$responseData = $this->openAIService->chat(\$user->match_user_id, "こんにちは");

         DB::table(\$tableName)->insert([
            'question' => "こんにちは",
            'answer' => \$responseData['choices'][0]['message']['content'],
        ]);

        \$this->info('Command executed successfully!');
    }
}
PHP;

        return $stub;
    }

    /**
     * コマンド名に基づいてシグネチャを取得registerCommandInKernel
     */
    protected function getCommandSignature($name)
    {
        return Str::kebab($name);
    }

    /**
     * Kernelにコマンドを登録
     */
    protected function registerCommandInKernel($commandName, $userId)
    {
        $kernelPath = app_path('Console/Kernel.php');
        $kernelContent = file_get_contents($kernelPath);
        // コマンドを登録
        if (strpos($kernelContent, 'protected $commands = [') !== false) {
            $newCommand = "        Commands\\{$commandName}Command::class,\n";
            $kernelContent = preg_replace(
                '/protected \$commands = \[(.*?)\];/s',
                "protected \$commands = [\n{$newCommand}$1];",
                $kernelContent
            );
        }

        // スケジュールを登録
        $this->registerScheduleInKernel($kernelContent, $commandName, $userId);

        return file_put_contents($kernelPath, $kernelContent);
    }

    /**
     * Kernelにコマンドのスケジュールを登録
     */
    protected function registerScheduleInKernel(&$kernelContent, $commandName, $userId)
    {
        $commandSignature = $this->getCommandSignature($commandName);
        $argument = isset($userId) && $userId ? " {$userId}" : "1";
        $newSchedule = "        \$schedule->command('{$commandSignature} {$argument}')->everyMinute();\n";

        // スケジュールメソッドが存在するか確認
        if (strpos($kernelContent, 'protected function schedule(Schedule $schedule)') !== false) {
            // スケジュールメソッド内にスケジュールを追加
            if (strpos($kernelContent, $newSchedule) === false) {  // 重複チェック
                $kernelContent = preg_replace(
                    '/protected function schedule\(Schedule \$schedule\)\s*{\s*/',
                    "protected function schedule(Schedule \$schedule)\n    {\n{$newSchedule}",
                    $kernelContent
                );
            }
        } else {
            // スケジュールメソッドが存在しない場合は作成
            $scheduleMethod = <<<PHP

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  \$schedule
     * @return void
     */
    protected function schedule(Schedule \$schedule)
    {
{$newSchedule}    }

PHP;
            $kernelContent = preg_replace(
                '/class Kernel extends ConsoleKernel/',
                "class Kernel extends ConsoleKernel{$scheduleMethod}",
                $kernelContent
            );
        }
    }
} 
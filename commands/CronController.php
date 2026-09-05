<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\Story;
use app\models\OnlineUser;

class CronController extends Controller
{
    public function actionCleanup()
    {
        $deletedStories = Story::deleteExpired();
        $deletedOnline = OnlineUser::cleanup();

        echo "Cleanup completed:\n";
        echo "  Expired stories deleted: $deletedStories\n";
        echo "  Stale online users deleted: $deletedOnline\n";
        echo "Done at " . date('Y-m-d H:i:s') . "\n";

        return ExitCode::OK;
    }
}

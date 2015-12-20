<?php
require __DIR__ . '/autoload.php';

use Upwork\API;
use Upwork\API\Config;
use Upwork\API\Client;
use Upwork\API\Routers\Jobs\Profile;
use Upwork\API\Routers\Jobs\Search;
use App\Classes\Db;
use App\Classes\Upwork;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\PHPConsoleHandler;


$configDb = json_decode(file_get_contents(__DIR__ . '/config.json'));
$db = new Db($configDb);

$accessToken = '6cd6202680a20796889a537ae28bb51e';
$accessSecret = '2d2c4ec57fd374c4';

$stream = new StreamHandler(__DIR__ . '/exceptions.log', Logger::DEBUG);
$browser = new BrowserConsoleHandler(Logger::DEBUG, true);
$phpConsole = new PHPConsoleHandler();
$logger = new Logger('rss_logger');
$logger->pushHandler($stream);
$logger->pushHandler($browser);
$logger->pushHandler($phpConsole);

$config = new Config(
    [
        'consumerKey' => '0b890e685dbaa9fddaf51df47f924096',
        'consumerSecret' => '70590fda6583b2db',
        'accessToken' => $accessToken,
        'accessSecret' => $accessSecret,
        'debug' => false,
        'authType' => 'OAuthPHPLib'
    ]
);

$client = new Client($config);
$client->getServer()->getInstance()->addServerToken($config::get('consumerKey'),
    'access', $accessToken, $accessSecret, 0);
$profile = new Profile($client);
$jobs = new Search($client);
$params = ['q' => '*', 'category2' => 'Web, Mobile & Software Dev', 'paging' => '0;100'];
$arrJobs = $jobs->find($params);
///////////
$jobIdArr = [];
foreach ($arrJobs->jobs as $i => $job) {
    $j = ':id' . $i;
    $jobIdArr[$j] = $job->id;
}
$sql = 'SELECT `id` FROM `upwork` WHERE `id` IN (' . implode(', ', array_keys($jobIdArr)) . ')';
$selectedIdArr = $db->dbSelectObj($sql, $jobIdArr);
$idArr = array_map(function ($item) {
    return $item->id;
}, $selectedIdArr);
$newIdArr = array_diff($jobIdArr, $idArr);
//////////
foreach ($arrJobs->jobs as $i => $job) {
//    $res = Upwork::findOne($db, $job->id);
//    if (!isset($res)) {
    if (in_array($job->id, $newIdArr)) {
        $upwork = new Upwork();
        $upwork->id = $job->id;
        $upwork->url = $job->url;
        $upwork->title = $job->title;
        $upwork->description = $job->snippet;
        $upwork->type = $job->job_type;
        $upwork->budget = $job->budget === null ? 0 : $job->budget;
        $upwork->engagement = $job->duration === null ? '' : $job->duration;
        $upwork->engagement_weeks = $job->workload === null ? '' : $job->workload;
        $upwork->skills = implode(', ', $job->skills);
        $upwork->rating = 0;
        try {
            $specific = $profile->getSpecific($job->id);
            $info = $specific->profile;
            $upwork->created_at = $info->op_ctime / 1000;
            $upwork->contractor_tier = $info->op_contractor_tier;
        } catch (OAuthException2 $e) {
            $logger->addInfo($e->getMessage());
            if (preg_match('#Profile.+is disabled#', $e->getMessage()) === 1) {
                $upwork->created_at = strtotime($job->date_created);
                $upwork->contractor_tier = 8;
            }
        }
        $upwork->insert($db);
    }
}


<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Http\Router;
use App\Http\Controllers\AppController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubmissionController;

// Basic security headers for admin UI
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');

$router = new Router();

// Public landing page
$router->add('GET', '/', function () {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Postra</title>'
       . '<style>html,body{height:100%;margin:0;background:#ffffff} .wrap{min-height:100%;display:grid;place-items:center} img{width:50%;max-width:512px;height:auto;}</style>'
        . '</head><body><div class="wrap"><img src="/images/logo.png" alt="Postra"></div></body></html>';
});

// Management UI (auth TBD)
$app = new AppController();
$router->add('GET', '/app', fn() => $app->dashboard());
$router->add('GET', '/app/', fn() => $app->dashboard());

// Auth
$auth = new AuthController();
$router->add('GET', '/app/login', fn() => $auth->showLogin());
$router->add('POST', '/app/login', fn() => $auth->login());
$router->add('POST', '/app/logout', fn() => $auth->logout());

// Projects
$projects = new ProjectController();
$router->add('GET', '/app/projects', fn() => $projects->index());
$router->add('GET', '/app/projects/new', fn() => $projects->newForm());
$router->add('POST', '/app/projects', fn() => $projects->create());
$router->add('GET', '/app/projects/{id}', fn($params) => $projects->show($params));
$router->add('POST', '/app/projects/{id}/settings', fn($params) => $projects->updateSettings($params));

// Forms
$forms = new FormController();
$router->add('POST', '/app/forms/new', fn() => $forms->create());
$router->add('GET', '/app/forms/{id}', fn($params) => $forms->show($params));
$router->add('POST', '/app/forms/{id}/settings', fn($params) => $forms->updateSettings($params));
$router->add('POST', '/app/forms/{id}/send-test', fn($params) => $forms->sendTest($params));

// Settings
$settings = new SettingsController();
$router->add('GET', '/app/settings/email', fn() => $settings->email());
$router->add('POST', '/app/settings/email', fn() => $settings->emailSave());
$router->add('POST', '/app/settings/email/test', fn() => $settings->emailTest());

// Submissions
$subs = new SubmissionController();
$router->add('GET', '/app/submissions', fn() => $subs->listAll());
$router->add('GET', '/app/submissions/export.csv', fn() => $subs->exportAllCsv());
$router->add('GET', '/app/forms/{id}/submissions', fn($params) => $subs->listForForm($params));
$router->add('GET', '/app/forms/{id}/submissions/export.csv', fn($params) => $subs->exportFormCsv($params));
$router->add('GET', '/app/submissions/{id}', fn($params) => $subs->show($params));
$router->add('POST', '/app/submissions/{id}/resend', fn($params) => $subs->resendEmail($params));
$router->add('GET', '/app/submissions/{id}/export.json', fn($params) => $subs->exportJson($params));
$router->add('POST', '/app/submissions/{id}/delete', fn($params) => $subs->delete($params));
$router->add('POST', '/app/submissions/bulk-delete', fn() => $subs->bulkDelete());

// Public capture endpoint
$capture = new CaptureController();
$router->add('POST', '/form/{public_id}', fn($params) => $capture->post($params));

// Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

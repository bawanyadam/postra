<?php

namespace App\Http\Controllers;

use App\Support\Session;
use App\Support\Csrf;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Security\Crypto;
use App\Infrastructure\Mail\SendGridMailer;
use PDO;

class SettingsController
{
    private function requireAuth(): bool
    {
        Session::start();
        if (empty($_SESSION['user'])) {
            header('Location: /app/login', true, 303);
            return false;
        }
        return true;
    }

    public function email(): void
    {
        if (!$this->requireAuth()) return;
        $pdo = Connection::pdo();
        $stmt = $pdo->query("SELECT id FROM api_credentials WHERE provider='sendgrid' AND scope='global' LIMIT 1");
        $exists = (bool)$stmt->fetchColumn();
        \App\Http\View::render('settings/email', [
            'configured' => $exists,
            'title' => 'Settings: Email',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => '/app'],
                ['label' => 'Settings', 'href' => '/app/settings/email'],
                ['label' => 'Email', 'href' => '/app/settings/email'],
            ],
        ]);
    }

    public function emailSave(): void
    {
        if (!$this->requireAuth()) return;
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { http_response_code(400); echo 'Invalid CSRF'; return; }
        $apiKey = trim((string)($_POST['api_key'] ?? ''));
        if ($apiKey === '') { $_SESSION['flash'] = 'API key cannot be empty.'; header('Location: /app/settings/email', true, 303); return; }
        $enc = Crypto::encrypt($apiKey);
        $pdo = Connection::pdo();
        $pdo->beginTransaction();
        try {
            $sel = $pdo->query("SELECT id FROM api_credentials WHERE provider='sendgrid' AND scope='global' LIMIT 1");
            $id = $sel->fetchColumn();
            if ($id) {
                $upd = $pdo->prepare('UPDATE api_credentials SET secret_encrypted=? WHERE id=?');
                $upd->execute([$enc, (int)$id]);
            } else {
                $ins = $pdo->prepare("INSERT INTO api_credentials (name, provider, scope, scope_ref_id, secret_encrypted) VALUES (?,?,?,?,?)");
                $ins->execute(['Global SendGrid', 'sendgrid', 'global', null, $enc]);
            }
            $pdo->commit();
            $_SESSION['flash'] = 'SendGrid key saved.';
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = 'Failed to save: ' . $e->getMessage();
        }
        header('Location: /app/settings/email', true, 303);
    }

    public function emailTest(): void
    {
        if (!$this->requireAuth()) return;
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { http_response_code(400); echo 'Invalid CSRF'; return; }
        $to = trim((string)($_POST['to'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $_SESSION['flash'] = 'Enter a valid test email.'; header('Location: /app/settings/email', true, 303); return; }
        $pdo = Connection::pdo();
        $row = $pdo->query("SELECT secret_encrypted FROM api_credentials WHERE provider='sendgrid' AND scope='global' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$row) { $_SESSION['flash'] = 'No global key configured.'; header('Location: /app/settings/email', true, 303); return; }
        try {
            $apiKey = Crypto::decrypt($row['secret_encrypted']);
            $mailer = new SendGridMailer();
            $ok = $mailer->send($apiKey, $to, 'Postra test email', '<p>This is a test email from Postra.</p>', 'This is a test email from Postra.');
            $_SESSION['flash'] = $ok ? 'Test email sent.' : 'SendGrid API returned a failure.';
        } catch (\Throwable $e) {
            $_SESSION['flash'] = 'Failed: ' . $e->getMessage();
        }
        header('Location: /app/settings/email', true, 303);
    }
}

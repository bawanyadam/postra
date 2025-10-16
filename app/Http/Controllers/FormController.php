<?php

namespace App\Http\Controllers;

use App\Support\Session;
use App\Support\Csrf;
use App\Support\Id;
use App\Infrastructure\Database\Connection;
use PDO;

class FormController
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

    public function create(): void
    {
        if (!$this->requireAuth()) return;
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }
        $projectId = (int)($_GET['project'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $recipient = trim((string)($_POST['recipient_email'] ?? ''));
        $redirect = trim((string)($_POST['redirect_url'] ?? ''));
        $allowed = trim((string)($_POST['allowed_domain'] ?? ''));
        $status = in_array($_POST['status'] ?? 'active', ['active','disabled'], true) ? $_POST['status'] : 'active';
        if (!$projectId || $name === '' || $recipient === '' || $redirect === '') {
            http_response_code(422);
            echo 'Missing required fields';
            return;
        }
        $pdo = Connection::pdo();
        $stmt = $pdo->prepare('INSERT INTO forms (public_id, project_id, name, recipient_email, redirect_url, allowed_domain, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([Id::ulid(), $projectId, $name, $recipient, $redirect, $allowed !== '' ? $allowed : null, $status]);
        header('Location: /app/projects/' . $projectId, true, 303);
    }

    public function show(array $params): void
    {
        if (!$this->requireAuth()) return;
        $id = (int)($params[0] ?? 0);
        $pdo = Connection::pdo();
        $stmt = $pdo->prepare('SELECT f.*, p.name as project_name FROM forms f JOIN projects p ON p.id = f.project_id WHERE f.id = ?');
        $stmt->execute([$id]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$form) { http_response_code(404); echo 'Form not found'; return; }
        \App\Http\View::render('forms/show', [
            'form' => $form,
            'title' => 'Form: ' . (string)$form['name'],
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => '/app'],
                ['label' => 'Projects', 'href' => '/app/projects'],
                ['label' => (string)$form['project_name'], 'href' => '/app/projects/' . (int)$form['project_id']],
                ['label' => (string)$form['name'], 'href' => '/app/forms/' . (int)$form['id']],
            ],
        ]);
    }

    public function updateSettings(array $params): void
    {
        if (!$this->requireAuth()) return;
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }
        $id = (int)($params[0] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $recipient = trim((string)($_POST['recipient_email'] ?? ''));
        $redirect = trim((string)($_POST['redirect_url'] ?? ''));
        $allowed = trim((string)($_POST['allowed_domain'] ?? ''));
        $status = in_array($_POST['status'] ?? 'active', ['active','disabled'], true) ? $_POST['status'] : 'active';
        if ($name === '' || $recipient === '' || $redirect === '') { http_response_code(422); echo 'Missing fields'; return; }
        $pdo = Connection::pdo();
        $stmt = $pdo->prepare('UPDATE forms SET name=?, recipient_email=?, redirect_url=?, allowed_domain=?, status=? WHERE id=?');
        $stmt->execute([$name, $recipient, $redirect, $allowed !== '' ? $allowed : null, $status, $id]);
        header('Location: /app/forms/' . $id, true, 303);
    }

    public function sendTest(array $params): void
    {
        if (!$this->requireAuth()) return;
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { http_response_code(400); echo 'Invalid CSRF token'; return; }
        $id = (int)($params[0] ?? 0);
        try {
            $pdo = Connection::pdo();
            $stmt = $pdo->prepare('SELECT f.id, f.name, f.project_id, f.recipient_email FROM forms f WHERE f.id = ?');
            $stmt->execute([$id]);
            $form = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$form) { http_response_code(404); echo 'Form not found'; return; }

            $cred = new \App\Services\CredentialService();
            $apiKey = $cred->resolveSendGridKey((int)$form['id'], (int)$form['project_id']);
            if (!$apiKey) { $_SESSION['flash'] = 'No SendGrid key configured (form/project/global).'; header('Location: /app/forms/' . $id, true, 303); return; }

            $payload = [ 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'message' => 'This is a test from Postra.' ];
            $meta = [
                'Test Email' => 'Yes',
                'Form' => (string)$form['name'],
            ];
            [$subject, $html, $text] = \App\Services\EmailTemplate::buildSubmissionEmail((string)$form['name'], $payload, $meta);
            $replyTo = $payload['email'];
            $mailer = new \App\Infrastructure\Mail\SendGridMailer();
            $ok = $mailer->send($apiKey, (string)$form['recipient_email'], $subject, $html, $text, $replyTo);
            $_SESSION['flash'] = $ok ? 'Test email sent to ' . $form['recipient_email'] : 'Failed to send test email.';
        } catch (\Throwable $e) {
            $_SESSION['flash'] = 'Failed to send: ' . $e->getMessage();
        }
        header('Location: /app/forms/' . $id, true, 303);
    }
}

<?php

namespace App\Http\Controllers;

use App\Infrastructure\Database\Connection;
use PDO;

class CaptureController
{
    public function post(array $params): void
    {
        $publicId = $params[0] ?? null;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Method Not Allowed';
            return;
        }

        if (!$publicId) {
            http_response_code(400);
            echo 'Bad Request';
            return;
        }

        $pdo = Connection::pdo();

        // Look up form by public_id
        $stmt = $pdo->prepare('SELECT id, name, redirect_url, status, recipient_email FROM forms WHERE public_id = ?');
        $stmt->execute([$publicId]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$form) {
            http_response_code(404);
            echo 'Form Not Found';
            return;
        }
        if ($form['status'] !== 'active') {
            http_response_code(403);
            echo 'Form Disabled';
            return;
        }

        $payload = $_POST;
        foreach ($payload as $k => $v) {
            if (str_starts_with($k, '_postra_')) {
                unset($payload[$k]);
            }
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Basic anti-spam measures (honeypot, timing threshold, per-IP throttle, dedupe window)
        // Honeypot: if reserved field _postra_hp is present and non-empty, treat as success but drop silently
        $redirect = $form['redirect_url'] ?? '/';
        if (!empty($_POST['_postra_hp'])) {
            header('Location: ' . $redirect, true, 303);
            return;
        }

        // Timing threshold: if client includes _postra_ts and it is too recent, drop
        $ts = isset($_POST['_postra_ts']) ? (int)$_POST['_postra_ts'] : 0;
        if ($ts > 0 && (time() - $ts) < 2) { // submitted in under 2s
            header('Location: ' . $redirect, true, 303);
            return;
        }

        // Simple heuristics for common spam payloads
        $spamDetector = new \App\Services\SpamDetector();
        if ($spamDetector->isSpam($payload)) {
            error_log('Postra: spam drop for form ' . $form['id'] . ' from ' . ($clientIp ?? 'unknown'));
            header('Location: ' . $redirect, true, 303);
            return;
        }

        // Per-IP throttle: one submission every 10 seconds per form
        try {
            $throttle = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE form_id = ? AND client_ip = INET6_ATON(?) AND created_at >= (NOW() - INTERVAL 10 SECOND)');
            $throttle->execute([$form['id'], $clientIp]);
            if ((int)$throttle->fetchColumn() > 0) {
                header('Location: ' . $redirect, true, 303);
                return;
            }
        } catch (\Throwable $_) {
            // best-effort only
        }

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO submissions (form_id, client_ip, user_agent, payload_json, dedupe_hash) VALUES (?, INET6_ATON(?), ?, CAST(? AS JSON), ?)');
            $normalized = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $dedupe = hash('sha256', $form['id'] . '|' . $normalized);
            // Dedupe: if same hash exists in last 5 minutes, treat as already-processed (no-op)
            try {
                $dupe = $pdo->prepare('SELECT id FROM submissions WHERE form_id = ? AND dedupe_hash = ? AND created_at >= (NOW() - INTERVAL 5 MINUTE) LIMIT 1');
                $dupe->execute([$form['id'], $dedupe]);
                if ($dupe->fetchColumn()) {
                    $pdo->rollBack();
                    header('Location: ' . $redirect, true, 303);
                    return;
                }
            } catch (\Throwable $_) {
                // continue; dedupe is best-effort
            }
            $ins->execute([$form['id'], $clientIp, $ua, $normalized, $dedupe]);
            $submissionId = (int)$pdo->lastInsertId();
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            http_response_code(500);
            header('Content-Type: text/plain');
            echo 'Failed to store submission. Did you run migrations? ' . $e->getMessage();
            return;
        }
        // Attempt email via SendGrid
        try {
            $to = (string)($form['recipient_email'] ?? '');
            if ($to === '') {
                error_log('Postra: form has no recipient_email; skipping email send for submission ' . $submissionId);
            } else {
                $this->sendEmail((int)$form['id'], $form['name'], $payload, $to, $submissionId, $clientIp, $ua);
            }
        } catch (\Throwable $e) {
            error_log('Email send failed: ' . $e->getMessage());
        }
        header('Location: ' . $redirect, true, 303);
    }

    private function sendEmail(int $formId, string $formName, array $payload, string $to, int $submissionId, ?string $ip, ?string $ua): void
    {
        $pdo = Connection::pdo();
        $prjStmt = $pdo->prepare('SELECT project_id FROM forms WHERE id = ?');
        $prjStmt->execute([$formId]);
        $projectId = (int)$prjStmt->fetchColumn();

        $credSvc = new \App\Services\CredentialService();
        $apiKey = $credSvc->resolveSendGridKey($formId, $projectId);
        if (!$apiKey) {
            error_log('No SendGrid API key configured');
            return; // skip silently in MVP
        }

        $meta = [
            'Submission ID' => (string)$submissionId,
            'IP' => (string)$ip,
            'User Agent' => (string)$ua,
            'Form' => $formName,
        ];
        [$subject, $html, $text] = \App\Services\EmailTemplate::buildSubmissionEmail($formName, $payload, $meta);

        $replyTo = null;
        foreach (['email','Email','reply_to','replyTo','_replyto','_reply_to'] as $key) {
            if (!empty($payload[$key]) && filter_var($payload[$key], FILTER_VALIDATE_EMAIL)) {
                $replyTo = $payload[$key];
                break;
            }
        }

        $mailer = new \App\Infrastructure\Mail\SendGridMailer();
        $mailer->send($apiKey, $to, $subject, $html, $text, $replyTo);
    }
}

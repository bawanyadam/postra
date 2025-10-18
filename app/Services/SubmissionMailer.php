<?php

namespace App\Services;

use App\Infrastructure\Database\Connection;

class SubmissionMailer
{
    /**
     * Send a submission payload via SendGrid.
     *
     * @param array<string,mixed> $payload
     * @param array<string,string> $metaExtras
     */
    public function send(int $formId, string $formName, array $payload, string $to, int $submissionId, ?string $ip, ?string $ua, array $metaExtras = []): bool
    {
        $pdo = Connection::pdo();
        $prjStmt = $pdo->prepare('SELECT project_id FROM forms WHERE id = ?');
        $prjStmt->execute([$formId]);
        $projectId = (int)$prjStmt->fetchColumn();

        $credSvc = new CredentialService();
        $apiKey = $credSvc->resolveSendGridKey($formId, $projectId);
        if (!$apiKey) {
            error_log('Postra: no SendGrid API key configured for form ' . $formId);
            return false;
        }

        $meta = array_merge([
            'Submission ID' => (string)$submissionId,
            'IP' => (string)$ip,
            'User Agent' => (string)$ua,
            'Form' => $formName,
        ], $metaExtras);

        [$subject, $html, $text] = EmailTemplate::buildSubmissionEmail($formName, $payload, $meta);

        $replyTo = null;
        foreach (['email','Email','reply_to','replyTo','_replyto','_reply_to'] as $key) {
            if (!empty($payload[$key]) && filter_var($payload[$key], FILTER_VALIDATE_EMAIL)) {
                $replyTo = (string)$payload[$key];
                break;
            }
        }

        $mailer = new \App\Infrastructure\Mail\SendGridMailer();
        return $mailer->send($apiKey, $to, $subject, $html, $text, $replyTo);
    }
}


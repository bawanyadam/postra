<?php

namespace App\Services;

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Security\Crypto;
use PDO;

class CredentialService
{
    public function resolveSendGridKey(?int $formId, ?int $projectId): ?string
    {
        $pdo = Connection::pdo();

        if ($formId) {
            $stmt = $pdo->prepare('SELECT ac.secret_encrypted FROM forms f JOIN api_credentials ac ON ac.id = f.api_credential_id WHERE f.id = ? AND ac.provider = \'sendgrid\'');
            $stmt->execute([$formId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                try {
                    return Crypto::decrypt($row['secret_encrypted']);
                } catch (\Throwable $_) {
                    // If decryption fails at form scope, try project/global next
                }
            }
        }

        if ($projectId) {
            $stmt = $pdo->prepare('SELECT secret_encrypted FROM api_credentials WHERE provider = \'sendgrid\' AND scope = \'project\' AND scope_ref_id = ?');
            $stmt->execute([$projectId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                try {
                    return Crypto::decrypt($row['secret_encrypted']);
                } catch (\Throwable $_) {
                    // If decryption fails at project scope, try global next
                }
            }
        }

        $stmt = $pdo->query('SELECT secret_encrypted FROM api_credentials WHERE provider = \'sendgrid\' AND scope = \'global\' LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            try {
                return Crypto::decrypt($row['secret_encrypted']);
            } catch (\Throwable $_) {
                // Fall through to null if even global decryption fails
            }
        }

        return null;
    }
}

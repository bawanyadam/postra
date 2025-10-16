<?php

namespace App\Http\Controllers;

use App\Support\Session;
use App\Infrastructure\Database\Connection;
use PDO;

class SubmissionController
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

    public function listForForm(array $params): void
    {
        if (!$this->requireAuth()) return;
        $formId = (int)($params[0] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = 25;
        $limit = $pageSize + 1; // fetch one extra to detect next page
        $offset = ($page - 1) * $pageSize;

        $pdo = Connection::pdo();
        $form = $pdo->prepare('SELECT id, name FROM forms WHERE id = ?');
        $form->execute([$formId]);
        $formRow = $form->fetch(PDO::FETCH_ASSOC);
        if (!$formRow) { http_response_code(404); echo 'Form not found'; return; }

        $stmt = $pdo->prepare('SELECT id, created_at, INET6_NTOA(client_ip) AS client_ip, user_agent, payload_json FROM submissions WHERE form_id = ? ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(1, $formId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasNext = count($rows) > $pageSize;
        $subs = array_slice($rows, 0, $pageSize);
        \App\Http\View::render('forms/submissions', [
            'form' => $formRow,
            'submissions' => $subs,
            'page' => $page,
            'hasNext' => $hasNext,
        ]);
    }

    public function listAll(): void
    {
        if (!$this->requireAuth()) return;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = 25;
        $limit = $pageSize + 1;
        $offset = ($page - 1) * $pageSize;

        $pdo = Connection::pdo();
        $stmt = $pdo->prepare('SELECT s.id, s.created_at, INET6_NTOA(s.client_ip) AS client_ip, s.payload_json, f.id AS form_id, f.name AS form_name, p.name AS project_name FROM submissions s JOIN forms f ON f.id = s.form_id JOIN projects p ON p.id = f.project_id ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasNext = count($rows) > $pageSize;
        $subs = array_slice($rows, 0, $pageSize);
        \App\Http\View::render('submissions/index', [
            'submissions' => $subs,
            'page' => $page,
            'hasNext' => $hasNext,
        ]);
    }

    public function show(array $params): void
    {
        if (!$this->requireAuth()) return;
        $id = (int)($params[0] ?? 0);
        $pdo = Connection::pdo();
        $stmt = $pdo->prepare('SELECT s.*, INET6_NTOA(s.client_ip) AS client_ip, f.name AS form_name, f.project_id FROM submissions s JOIN forms f ON f.id = s.form_id WHERE s.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); echo 'Submission not found'; return; }
        \App\Http\View::render('submissions/show', ['submission' => $row]);
    }
}

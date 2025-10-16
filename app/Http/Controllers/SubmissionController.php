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

        $sql = 'SELECT id, created_at, INET6_NTOA(client_ip) AS client_ip, user_agent, payload_json
                FROM submissions
                WHERE form_id = :form_id
                ORDER BY created_at DESC
                LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':form_id', $formId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasNext = count($rows) > $pageSize;
        $subs = array_slice($rows, 0, $pageSize);
        \App\Http\View::render('forms/submissions', [
            'form' => $formRow,
            'submissions' => $subs,
            'page' => $page,
            'hasNext' => $hasNext,
            'title' => 'Submissions: ' . (string)$formRow['name'],
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
        $sql = 'SELECT s.id, s.created_at, INET6_NTOA(s.client_ip) AS client_ip, s.payload_json,
                       f.id AS form_id, f.name AS form_name, p.name AS project_name
                FROM submissions s
                JOIN forms f ON f.id = s.form_id
                JOIN projects p ON p.id = f.project_id
                ORDER BY s.created_at DESC
                LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasNext = count($rows) > $pageSize;
        $subs = array_slice($rows, 0, $pageSize);
        \App\Http\View::render('submissions/index', [
            'submissions' => $subs,
            'page' => $page,
            'hasNext' => $hasNext,
            'title' => 'Submissions',
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
        \App\Http\View::render('submissions/show', ['submission' => $row, 'title' => 'Submission #' . (string)$id]);
    }

    public function exportAllCsv(): void
    {
        if (!$this->requireAuth()) return;
        $pdo = Connection::pdo();
        $sql = 'SELECT s.id, s.created_at, INET6_NTOA(s.client_ip) AS client_ip, s.user_agent, s.payload_json, f.name AS form_name, p.name AS project_name
                FROM submissions s
                JOIN forms f ON f.id = s.form_id
                JOIN projects p ON p.id = f.project_id
                ORDER BY s.created_at DESC';
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $fieldSet = [];
        $payloads = [];
        foreach ($rows as $r) {
            $payload = json_decode($r['payload_json'] ?? '[]', true) ?: [];
            $payloads[] = $payload;
            foreach ($payload as $k => $_) { $fieldSet[$k] = true; }
        }
        $payloadFields = array_keys($fieldSet);
        sort($payloadFields, SORT_NATURAL | SORT_FLAG_CASE);

        $headers = array_merge(['id','created_at','project','form','client_ip','user_agent'], $payloadFields);
        $filename = 'submissions_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $i => $r) {
            $payload = $payloads[$i] ?? [];
            $base = [
                $r['id'],
                $r['created_at'],
                (string)($r['project_name'] ?? ''),
                (string)($r['form_name'] ?? ''),
                (string)($r['client_ip'] ?? ''),
                (string)($r['user_agent'] ?? ''),
            ];
            $extra = [];
            foreach ($payloadFields as $f) {
                $v = $payload[$f] ?? '';
                if (is_array($v)) { $v = implode(', ', array_map('strval', $v)); }
                elseif (is_object($v)) { $v = json_encode($v); }
                $extra[] = (string)$v;
            }
            fputcsv($out, array_merge($base, $extra));
        }
        fclose($out);
    }

    public function exportFormCsv(array $params): void
    {
        if (!$this->requireAuth()) return;
        $formId = (int)($params[0] ?? 0);
        $pdo = Connection::pdo();
        $form = $pdo->prepare('SELECT id, name FROM forms WHERE id = ?');
        $form->execute([$formId]);
        $formRow = $form->fetch(PDO::FETCH_ASSOC);
        if (!$formRow) { http_response_code(404); echo 'Form not found'; return; }

        $sql = 'SELECT id, created_at, INET6_NTOA(client_ip) AS client_ip, user_agent, payload_json
                FROM submissions
                WHERE form_id = :form_id
                ORDER BY created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':form_id', $formId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fieldSet = [];
        $payloads = [];
        foreach ($rows as $r) {
            $payload = json_decode($r['payload_json'] ?? '[]', true) ?: [];
            $payloads[] = $payload;
            foreach ($payload as $k => $_) { $fieldSet[$k] = true; }
        }
        $payloadFields = array_keys($fieldSet);
        sort($payloadFields, SORT_NATURAL | SORT_FLAG_CASE);

        $headers = array_merge(['id','created_at','client_ip','user_agent'], $payloadFields);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/','_', $formRow['name'])));
        $filename = 'form_' . ($slug !== '' ? $slug : $formRow['id']) . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $i => $r) {
            $payload = $payloads[$i] ?? [];
            $base = [
                $r['id'],
                $r['created_at'],
                (string)($r['client_ip'] ?? ''),
                (string)($r['user_agent'] ?? ''),
            ];
            $extra = [];
            foreach ($payloadFields as $f) {
                $v = $payload[$f] ?? '';
                if (is_array($v)) { $v = implode(', ', array_map('strval', $v)); }
                elseif (is_object($v)) { $v = json_encode($v); }
                $extra[] = (string)$v;
            }
            fputcsv($out, array_merge($base, $extra));
        }
        fclose($out);
    }
}

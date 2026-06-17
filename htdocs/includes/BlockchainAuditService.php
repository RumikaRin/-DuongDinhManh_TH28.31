<?php

declare(strict_types=1);

function blockchain_audit_config(): array
{
    return [
        'enabled' => getenv('BLOCKCHAIN_ENABLED') === '1',
        'network' => getenv('BLOCKCHAIN_NETWORK') ?: 'local-disabled',
        'chain_id' => (int)(getenv('BLOCKCHAIN_CHAIN_ID') ?: 0),
        'contract_address' => getenv('BLOCKCHAIN_CONTRACT_ADDRESS') ?: '',
        'explorer_base_url' => getenv('BLOCKCHAIN_EXPLORER_BASE_URL') ?: '',
    ];
}

function blockchain_audit_enabled(): bool
{
    return blockchain_audit_config()['enabled'] === true;
}

function blockchain_audit_is_assoc(array $value): bool
{
    if ($value === []) {
        return false;
    }

    return array_keys($value) !== range(0, count($value) - 1);
}

function blockchain_audit_canonicalize($value)
{
    if (!is_array($value)) {
        return $value;
    }

    if (blockchain_audit_is_assoc($value)) {
        ksort($value);
    }

    foreach ($value as $key => $nested) {
        $value[$key] = blockchain_audit_canonicalize($nested);
    }

    return $value;
}

function blockchain_audit_json(array $payload): string
{
    return json_encode(
        blockchain_audit_canonicalize($payload),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?: '{}';
}

function blockchain_audit_payload_hash(array $payload): string
{
    return '0x' . hash('sha256', blockchain_audit_json($payload));
}

function blockchain_audit_sensitive_keys(): array
{
    return [
        'address',
        'comment',
        'content',
        'diachi',
        'email',
        'email_tv',
        'ghichu',
        'hoten',
        'mk_tv',
        'name',
        'password',
        'phone',
        'reply',
        'sdt',
        'sdt_tv',
        'ten_tv',
    ];
}

function blockchain_audit_sanitize_payload(array $payload): array
{
    $safe = [];
    $sensitive = array_flip(blockchain_audit_sensitive_keys());

    foreach ($payload as $key => $value) {
        $normalizedKey = mb_strtolower((string)$key, 'UTF-8');
        if (isset($sensitive[$normalizedKey])) {
            continue;
        }

        if (is_array($value)) {
            $safe[$key] = blockchain_audit_sanitize_payload($value);
        } elseif (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            $safe[$key] = $value;
        } else {
            $safe[$key] = (string)$value;
        }
    }

    return $safe;
}

function blockchain_audit_actor(array $actor): array
{
    $type = isset($actor['type']) ? (string)$actor['type'] : 'system';
    $id = isset($actor['id']) ? (int)$actor['id'] : 0;
    $safe = ['type' => $type, 'id' => $id];

    if (isset($actor['name']) && trim((string)$actor['name']) !== '') {
        $safe['name_hash'] = blockchain_audit_payload_hash(['name' => (string)$actor['name']]);
    }

    return $safe;
}

function blockchain_audit_latest_event_hash(mysqli $db): ?string
{
    $result = $db->query(
        "SELECT event_hash FROM blockchain_audit_events
         WHERE event_hash IS NOT NULL AND event_hash <> ''
         ORDER BY id DESC
         LIMIT 1"
    );

    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return $row ? (string)$row['event_hash'] : null;
}

function blockchain_audit_current_actor(string $type = 'admin'): array
{
    return [
        'type' => $type,
        'id' => isset($_SESSION['id_tv']) ? (int)$_SESSION['id_tv'] : 0,
        'name' => isset($_SESSION['ten_tv']) ? (string)$_SESSION['ten_tv'] : $type,
    ];
}

function blockchain_audit_record(
    mysqli $db,
    string $entityType,
    int $entityId,
    string $action,
    array $payload = [],
    array $actor = []
): int {
    if ($entityType === '' || $entityId <= 0 || $action === '') {
        return 0;
    }

    $config = blockchain_audit_config();
    $safeActor = blockchain_audit_actor($actor);
    $safePayload = blockchain_audit_sanitize_payload($payload);
    $eventPayload = [
        'schema_version' => 1,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'action' => $action,
        'actor' => $safeActor,
        'payload' => $safePayload,
    ];
    $payloadJson = blockchain_audit_json($eventPayload);
    $payloadHash = blockchain_audit_payload_hash($eventPayload);
    $previousHash = blockchain_audit_latest_event_hash($db);
    $eventHash = blockchain_audit_payload_hash([
        'schema_version' => 1,
        'previous_hash' => $previousHash,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'action' => $action,
        'actor' => $safeActor,
        'payload_hash' => $payloadHash,
    ]);
    $status = $config['enabled'] ? 'pending' : 'disabled';
    $actorType = $safeActor['type'];
    $actorId = $safeActor['id'];
    $piiPolicy = 'raw PII omitted; private text fields removed before hashing';
    $errorMessage = $config['enabled'] && $config['contract_address'] === ''
        ? 'Blockchain enabled but contract address is not configured.'
        : null;

    $stmt = $db->prepare(
        'INSERT INTO blockchain_audit_events
            (entity_type, entity_id, action, actor_type, actor_id, payload_hash, previous_hash, event_hash, payload_json, pii_policy, status, error_message)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        error_log('Blockchain audit insert prepare failed: ' . $db->error);
        return 0;
    }

    $stmt->bind_param(
        'sississsssss',
        $entityType,
        $entityId,
        $action,
        $actorType,
        $actorId,
        $payloadHash,
        $previousHash,
        $eventHash,
        $payloadJson,
        $piiPolicy,
        $status,
        $errorMessage
    );

    if (!$stmt->execute()) {
        error_log('Blockchain audit insert failed: ' . $stmt->error);
        $stmt->close();
        return 0;
    }

    $auditId = (int)$stmt->insert_id;
    $stmt->close();

    return $auditId;
}

function blockchain_audit_latest_for_entity(mysqli $db, string $entityType, int $entityId): ?array
{
    $stmt = $db->prepare(
        'SELECT e.*, r.tx_hash, r.block_number, r.network, r.contract_address
         FROM blockchain_audit_events e
         LEFT JOIN blockchain_receipts r ON r.audit_event_id = e.id
         WHERE e.entity_type = ? AND e.entity_id = ?
         ORDER BY e.id DESC
         LIMIT 1'
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('si', $entityType, $entityId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $row;
}

function blockchain_audit_status_badge(?array $event): string
{
    if (!$event) {
        return '<span class="blockchain-badge blockchain-badge-empty">Chưa có blockchain proof</span>';
    }

    $status = (string)($event['status'] ?? 'pending');
    $labels = [
        'confirmed' => 'Blockchain confirmed',
        'pending' => 'Blockchain pending',
        'failed' => 'Blockchain failed',
        'disabled' => 'Blockchain disabled',
    ];
    $label = $labels[$status] ?? 'Blockchain pending';
    $hash = htmlspecialchars((string)($event['payload_hash'] ?? ''), ENT_QUOTES, 'UTF-8');

    return '<span class="blockchain-badge blockchain-badge-' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '" title="' . $hash . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}

<?php
declare(strict_types=1);

/** Registro de ações críticas. Sempre grava a identidade REAL de quem agiu (inclusive desenvolvedores). */
final class Audit
{
    public static function log(string $action, ?string $entity = null, ?int $entityId = null, array $meta = [], ?int $actorId = null): void
    {
        $user = Auth::user();
        DB::insert('audit_logs', [
            'actor_id' => $actorId ?? ($user['id'] ?? null),
            'acting_as' => $user ? Auth::mode() : null,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta' => $meta ? json_enc($meta) : null,
            'ip' => PHP_SAPI === 'cli' ? 'cli' : request_ip(),
            'created_at' => now(),
        ]);
    }
}

<?php
declare(strict_types=1);

/** Parâmetros operacionais editáveis pelo Renan na própria interface. */
final class Settings
{
    private static ?array $cache = null;

    public const DEFAULTS = [
        'brand_name' => 'Wruch Personal',
        'trainer_name' => 'Renan Wruch',
        'accent_color' => '#2f5d50',
        'public_headline' => 'Consultoria esportiva online com acompanhamento individual',
        'public_intro' => 'Treinos planejados para a sua rotina, o seu esporte e o seu objetivo, com acompanhamento próximo do Renan pela plataforma.',
        'public_about' => '',
        'public_photo_file_id' => '',
        'contact_whatsapp' => '5519998089854',
        'contact_whatsapp_label' => '+55 19 99808-9854',
        'contact_instagram' => 'https://www.instagram.com/renanwruch/',
        'contact_email' => 'renanrdw@gmail.com',
        'billing_policy' => 'off',          // off | restrict
        'billing_grace_days' => '7',
        'workout_stale_days' => '45',       // treinos mais antigos que isto aparecem como "precisam de atualização"
        'chat_digest_minutes' => '15',
        'trainer_digest_hour' => '8',
        'upload_image_mb' => '15',
        'upload_video_mb' => '200',
        'upload_audio_mb' => '25',
        'upload_document_mb' => '20',
        'retention_months_after_end' => '0', // 0 = não excluir automaticamente
        'default_self_eval_days' => '7',
        'default_assessment_days' => '30',
        'notify_trainer_new_signup' => '1',
        'notify_trainer_assessment' => '1',
        'notify_trainer_requests' => '1',
        'notify_trainer_daily_digest' => '1',
    ];

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (DB::all('SELECT setting_key, setting_value FROM settings') as $r) {
                    self::$cache[$r['setting_key']] = $r['setting_value'];
                }
            } catch (PDOException) {
                // Banco ainda não migrado.
            }
        }
        return self::$cache + self::DEFAULTS;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        $exists = DB::val('SELECT COUNT(*) FROM settings WHERE setting_key = ?', [$key]);
        if ($exists) {
            DB::update('settings', ['setting_value' => $value, 'updated_at' => now()], 'setting_key = ?', [$key]);
        } else {
            DB::insert('settings', ['setting_key' => $key, 'setting_value' => $value, 'updated_at' => now()]);
        }
        self::$cache = null;
    }

    public static function int(string $key): int
    {
        return (int) self::get($key, self::DEFAULTS[$key] ?? 0);
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}

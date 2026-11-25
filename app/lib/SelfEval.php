<?php
declare(strict_types=1);

/** Escala de autoavaliação (1–5). As notas são percepções do aluno, não diagnósticos. */
final class SelfEval
{
    /** Escala com expressões próprias (SVG), do vermelho suave ao verde. */
    public const SCALE = [
        1 => ['label' => 'Muito ruim', 'tone' => 'lv1'],
        2 => ['label' => 'Ruim', 'tone' => 'lv2'],
        3 => ['label' => 'Regular', 'tone' => 'lv3'],
        4 => ['label' => 'Bom', 'tone' => 'lv4'],
        5 => ['label' => 'Excelente', 'tone' => 'lv5'],
    ];

    /** Traços de cada expressão (viewBox 64×64): sobrancelhas/olhos/boca. */
    private const FEATURES = [
        1 => '<path class="ft" d="M18.5 22.5 27 18.8M45.5 22.5 37 18.8"/><circle class="eye" cx="24" cy="29.5" r="2.7"/><circle class="eye" cx="40" cy="29.5" r="2.7"/><path class="ft mouth" d="M22 46.5Q32 37.5 42 46.5"/>',
        2 => '<circle class="eye" cx="24" cy="28.5" r="2.7"/><circle class="eye" cx="40" cy="28.5" r="2.7"/><path class="ft mouth" d="M23.5 44Q32 39.5 40.5 44"/>',
        3 => '<circle class="eye" cx="24" cy="28.5" r="2.7"/><circle class="eye" cx="40" cy="28.5" r="2.7"/><path class="ft mouth" d="M24 42.5H40"/>',
        4 => '<circle class="eye" cx="24" cy="28" r="2.7"/><circle class="eye" cx="40" cy="28" r="2.7"/><path class="ft mouth" d="M22.5 39Q32 47.5 41.5 39"/>',
        5 => '<path class="ft" d="M19 30Q24 24 29 30M35 30Q40 24 45 30"/><path class="fill mouth" d="M21 37.5H43Q43 49 32 49Q21 49 21 37.5Z"/>',
    ];

    /** Expressão vetorial do nível (1–5). Decorativa: o texto do nível acompanha sempre. */
    public static function face(int $level, string $cls = ''): string
    {
        $level = max(1, min(5, $level));
        return '<svg class="face ' . self::SCALE[$level]['tone'] . ($cls !== '' ? ' ' . e($cls) : '') . '" viewBox="0 0 64 64" aria-hidden="true" focusable="false">'
            . '<circle class="bg" cx="32" cy="32" r="29"/>' . self::FEATURES[$level] . '</svg>';
    }

    public const DESCRIPTIONS = [
        'sono' => [1 => 'Dormi muito pouco ou acordei várias vezes.', 2 => 'Dormi menos que o normal e acordei cansado.', 3 => 'Sono razoável, nem bom nem ruim.', 4 => 'Dormi bem na maior parte dos dias.', 5 => 'Sono profundo e acordei descansado.'],
        'alimentacao' => [1 => 'Comi de forma muito desorganizada.', 2 => 'Pulei refeições ou exagerei com frequência.', 3 => 'Alimentação razoável, com alguns deslizes.', 4 => 'Comi bem e com regularidade.', 5 => 'Alimentação organizada e do jeito planejado.'],
        'treinamento' => [1 => 'Não consegui treinar ou os treinos foram muito ruins.', 2 => 'Treinei pouco ou com muita dificuldade.', 3 => 'Treinei, mas abaixo do esperado.', 4 => 'Cumpri os treinos com bom rendimento.', 5 => 'Treinos completos e com ótimo rendimento.'],
        'cardio' => [1 => 'Não fiz o cardio previsto.', 2 => 'Fiz bem menos que o previsto.', 3 => 'Fiz parte do previsto.', 4 => 'Fiz quase tudo o que estava previsto.', 5 => 'Fiz todo o cardio previsto, me sentindo bem.'],
        'disposicao' => [1 => 'Muito cansado na maior parte do tempo.', 2 => 'Pouca energia em vários momentos.', 3 => 'Energia oscilando ao longo dos dias.', 4 => 'Boa disposição na maior parte do tempo.', 5 => 'Muita energia e disposição.'],
        'recuperacao' => [1 => 'Muito dolorido, sem conseguir me recuperar.', 2 => 'Recuperação lenta entre os treinos.', 3 => 'Recuperação razoável.', 4 => 'Recupero bem entre os treinos.', 5 => 'Totalmente recuperado para treinar.'],
        'motivacao' => [1 => 'Sem nenhuma vontade de treinar.', 2 => 'Pouca motivação.', 3 => 'Motivação oscilando.', 4 => 'Motivado na maior parte do tempo.', 5 => 'Muito motivado e engajado.'],
        'hidratacao' => [1 => 'Bebi muito pouca água.', 2 => 'Bebi menos água do que deveria.', 3 => 'Hidratação razoável.', 4 => 'Bebi água regularmente.', 5 => 'Me hidratei muito bem todos os dias.'],
    ];

    /** Indicadores simples de atenção: notas baixas (≤ 2) ou queda de 2+ pontos em relação à anterior. */
    public static function attention(array $scores, ?array $previous): array
    {
        $flags = [];
        foreach ($scores as $cat => $v) {
            $v = (int) $v;
            if ($v <= 2) {
                $flags[$cat] = 'nota baixa';
            } elseif ($previous && isset($previous[$cat]) && (int) $previous[$cat] - $v >= 2) {
                $flags[$cat] = 'queda';
            }
        }
        return $flags;
    }
}

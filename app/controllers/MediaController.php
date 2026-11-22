<?php
declare(strict_types=1);

final class MediaController
{
    public function show(int $id): void
    {
        $file = Files::find($id) ?? abort(404, 'Arquivo não encontrado.');
        $public = in_array($file['context'], ['public', 'brand'], true);
        if (!$public) {
            $user = Auth::user();
            if (!$user) abort(401, 'Faça login para ver este arquivo.');
            // URL temporária vinculada ao usuário + regra de acesso por contexto.
            if (!Files::checkSignature($id, (int) input('e', 0), (string) input('s', ''))) {
                abort(403, 'Link expirado. Recarregue a página.');
            }
            if (!Files::canView($user, $file)) {
                abort(403, 'Você não tem acesso a este arquivo.');
            }
        }
        Files::serve($file, (bool) input('download'));
    }
}

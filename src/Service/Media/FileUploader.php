<?php

namespace App\Service\Media;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Salva um upload em public/uploads/{subdir}/ com um nome único, e devolve
 * o caminho público (ex.: "/uploads/teams/uniao-fc-6710a1b2c3.png") pronto
 * para gravar em Team::logo — um simples campo de texto com a URL, não um
 * relacionamento de arquivo à parte.
 */
class FileUploader
{
    public function __construct(
        private readonly string $publicDir,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $file, string $subdir): string
    {
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($originalName)->lower();
        $fileName = sprintf('%s-%s.%s', $safeName, bin2hex(random_bytes(4)), $extension);

        $targetDir = $this->publicDir.'/uploads/'.$subdir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $file->move($targetDir, $fileName);

        return '/uploads/'.$subdir.'/'.$fileName;
    }
}

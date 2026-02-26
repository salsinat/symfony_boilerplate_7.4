<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger
    ) {
    }

    public function upload(UploadedFile $file, string $subDirectory = ''): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $targetPath = $this->targetDirectory;
        if ($subDirectory) {
            $targetPath .= '/' . $subDirectory;
        }

        // Create directory if it doesn't exist
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0777, true);
        }

        try {
            $file->move($targetPath, $fileName);
        } catch (FileException $e) {
            throw new \Exception('Erreur lors de l\'upload du fichier: ' . $e->getMessage());
        }

        return $fileName;
    }

    public function remove(string $filename, string $subDirectory = ''): bool
    {
        $targetPath = $this->targetDirectory;
        if ($subDirectory) {
            $targetPath .= '/' . $subDirectory;
        }

        $filePath = $targetPath . '/' . $filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}

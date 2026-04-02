<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Http\UploadedFile;

class PDFParserService
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function extractText(UploadedFile $file): string
    {
        $pdf = $this->parser->parseFile($file->getRealPath());
        $text = '';

        foreach ($pdf->getPages() as $page) {
            $text .= $page->getText() . "\n\n";
        }

        return trim($text);
    }

    public function getPageCount(UploadedFile $file): int
    {
        $pdf = $this->parser->parseFile($file->getRealPath());
        return count($pdf->getPages());
    }

    public function extractMetadata(UploadedFile $file): array
    {
        $pdf = $this->parser->parseFile($file->getRealPath());
        $details = $pdf->getDetails();

        return [
            'title' => $details['Title'] ?? null,
            'author' => $details['Author'] ?? null,
            'subject' => $details['Subject'] ?? null,
            'creator' => $details['Creator'] ?? null,
            'producer' => $details['Producer'] ?? null,
            'creation_date' => $details['CreationDate'] ?? null,
        ];
    }

    public function processInChunks(string $text, int $chunkSize = 1000): array
    {
        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk) + strlen($sentence) <= $chunkSize) {
                $currentChunk .= ' ' . $sentence;
            } else {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                }
                $currentChunk = $sentence;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}

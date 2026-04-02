<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\PDFParserService;
use App\Services\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    protected PDFParserService $pdfService;
    protected YouTubeService $youtubeService;

    public function __construct(PDFParserService $pdfService, YouTubeService $youtubeService)
    {
        $this->pdfService = $pdfService;
        $this->youtubeService = $youtubeService;
    }

    public function index(Request $request): JsonResponse
    {
        $documents = $request->user()
            ->documents()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['documents' => $documents]);
    }

    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf|max:20480', // 20MB max
            'title' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $title = $request->title ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Save file
        $uuid = Str::uuid();
        $filename = $uuid . '.pdf';
        $path = 'documents/' . $request->user()->uuid . '/' . $filename;
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        // Create document record
        $document = Document::create([
            'uuid' => $uuid,
            'user_id' => $request->user()->id,
            'title' => $title,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'page_count' => $this->pdfService->getPageCount($file),
            'source_type' => 'pdf',
            'status' => 'pending',
        ]);

        // Extract text in background (sync for now)
        try {
            $text = $this->pdfService->extractText($file);
            $document->update([
                'extracted_text' => $text,
                'status' => 'completed',
            ]);
        } catch (\Exception $e) {
            $document->update(['status' => 'failed']);
            return response()->json(['error' => 'Failed to parse PDF'], 500);
        }

        return response()->json([
            'document' => $document,
            'message' => 'PDF uploaded and processed successfully',
        ], 201);
    }

    public function addYouTube(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|url',
            'title' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $videoId = $this->youtubeService->extractVideoId($request->url);

        if (!$videoId) {
            return response()->json(['error' => 'Invalid YouTube URL'], 400);
        }

        $videoInfo = $this->youtubeService->getVideoInfo($videoId);

        if (!$videoInfo) {
            return response()->json(['error' => 'Could not fetch YouTube video info'], 400);
        }

        $document = Document::create([
            'uuid' => Str::uuid(),
            'user_id' => $request->user()->id,
            'title' => $request->title ?? $videoInfo['title'],
            'source_type' => 'youtube',
            'status' => 'pending',
        ]);

        // Get transcript
        try {
            $transcript = $this->youtubeService->getTranscript($videoId);

            if ($transcript) {
                $document->update([
                    'extracted_text' => $transcript,
                    'status' => 'completed',
                ]);
            } else {
                $document->update([
                    'extracted_text' => 'Transcript not available for this video.',
                    'status' => 'completed',
                ]);
            }
        } catch (\Exception $e) {
            $document->update(['status' => 'failed']);
            return response()->json(['error' => 'Failed to fetch transcript'], 500);
        }

        return response()->json([
            'document' => $document,
            'video_info' => $videoInfo,
            'message' => 'YouTube video added successfully',
        ], 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $document = $request->user()
            ->documents()
            ->where('uuid', $uuid)
            ->first();

        if (!$document) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        return response()->json(['document' => $document]);
    }

    public function content(Request $request, string $uuid): JsonResponse
    {
        $document = $request->user()
            ->documents()
            ->where('uuid', $uuid)
            ->first();

        if (!$document) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        return response()->json([
            'extracted_text' => $document->extracted_text,
            'title' => $document->title,
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $document = $request->user()
            ->documents()
            ->where('uuid', $uuid)
            ->first();

        if (!$document) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        // Delete file if exists
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }
}

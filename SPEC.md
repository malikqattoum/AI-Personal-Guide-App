# StudyAI - AI Personal Study Guide

An AI-powered mobile application that transforms PDFs and YouTube videos into flashcards and audio summaries using a WhatsApp-style chat interface.

## Tech Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.1+)
- **Authentication**: Laravel Sanctum
- **AI Services**: OpenAI (GPT-4o-mini, TTS-1)
- **Database**: SQLite (development) / MySQL (production)
- **PDF Parsing**: smalot/pdfparser

### Frontend
- **Framework**: Flutter 3.x
- **State Management**: flutter_bloc (Cubit)
- **Architecture**: Clean Architecture
- **HTTP Client**: Dio
- **Secure Storage**: flutter_secure_storage

## Features

1. **User Authentication**
   - Register/Login with email
   - Secure token-based auth via Sanctum

2. **Document Management**
   - Upload PDF files
   - Add YouTube videos via URL
   - Extract text from PDFs
   - Fetch YouTube transcripts

3. **AI-Powered Features**
   - Generate 5 flashcards from document content
   - Create 2-minute audio summaries (TTS)
   - Chat with AI about document content

4. **WhatsApp-Style Chat**
   - Real-time messaging interface
   - Typing indicators
   - Message bubbles (user/assistant)

5. **Flashcard System**
   - Flip animation
   - Progress tracking
   - Review statistics

6. **Study Sessions**
   - Track study time
   - Session statistics
   - Study streak

## API Endpoints

### Auth
- `POST /api/auth/register` - Register user
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user

### Documents
- `GET /api/documents` - List documents
- `POST /api/documents/upload` - Upload PDF
- `POST /api/documents/youtube` - Add YouTube video
- `GET /api/documents/{uuid}` - Get document
- `GET /api/documents/{uuid}/content` - Get extracted text
- `DELETE /api/documents/{uuid}` - Delete document

### Flashcards
- `GET /api/flashcards` - List flashcards
- `POST /api/flashcards/generate` - Generate 5 flashcards
- `PUT /api/flashcards/{uuid}/review` - Review flashcard
- `DELETE /api/flashcards/{uuid}` - Delete flashcard

### Audio
- `POST /api/audio/generate` - Generate audio summary
- `GET /api/audio/{uuid}/stream` - Stream audio

### Chat
- `GET /api/chat/messages` - Get chat history
- `POST /api/chat/message` - Send message
- `DELETE /api/chat/messages` - Clear chat

### Sessions
- `GET /api/sessions` - List sessions
- `POST /api/sessions/start` - Start session
- `PUT /api/sessions/{uuid}/end` - End session
- `GET /api/sessions/stats` - Get statistics

## Setup Instructions

### Backend
```bash
cd backend
composer install
cp .env.example .env
# Edit .env with your OpenAI API key
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend
```bash
cd frontend/studyai_app
flutter pub get
flutter run
```

## Environment Variables

### Backend (.env)
```
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4o-mini
YOUTUBE_API_KEY=optional_youtube_api_key
```

## Project Structure

```
C:\xampp\htdocs\AI-Resume-Tailor\
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/migrations/
│   └── routes/api.php
├── frontend/
│   └── studyai_app/
│       └── lib/
│           ├── core/
│           ├── data/
│           └── presentation/
└── SPEC.md
```

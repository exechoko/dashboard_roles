<?php

namespace App\Http\Controllers;

use App\Http\Requests\AskChatbotRequest;
use App\Jobs\ProcessChatbotMessage;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Services\ChatbotContentSanitizer;
use App\Services\OpenCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function __construct(private ChatbotContentSanitizer $contentSanitizer)
    {
    }

    public function history(Request $request): JsonResponse
    {
        $conversation = $request->user()->chatbotConversations()
            ->with(['messages' => fn ($query) => $query->orderBy('id')])
            ->latest('id')
            ->first();

        return response()->json([
            'conversation_id' => $conversation?->id,
            'messages' => $conversation?->messages
                ->map(fn (ChatbotMessage $message): array => $this->messageData($message))
                ->values() ?? [],
        ]);
    }

    public function ask(AskChatbotRequest $request): JsonResponse
    {
        $question = $this->contentSanitizer->sanitizeInput($request->string('question')->toString());

        [$conversation, $assistantMessage] = DB::transaction(function () use ($request, $question): array {
            $conversation = $request->user()->chatbotConversations()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($conversation?->messages()->whereIn('status', ['pending', 'processing'])->exists()) {
                abort(409, 'Esperá a que finalice la consulta anterior.');
            }

            if ($conversation === null) {
                $conversation = ChatbotConversation::create([
                    'user_id' => $request->user()->id,
                    'title' => Str::limit($question, 120, ''),
                ]);
            }

            $conversation->messages()->create([
                'role' => 'user',
                'content' => $question,
                'status' => 'completed',
                'context_path' => $request->input('context_path'),
            ]);

            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'status' => 'pending',
            ]);

            return [$conversation, $assistantMessage];
        });

        ProcessChatbotMessage::dispatchAfterResponse($assistantMessage->id);

        return response()->json([
            'conversation_id' => $conversation->id,
            'message' => $this->messageData($assistantMessage),
        ], 202);
    }

    public function status(Request $request, ChatbotMessage $message): JsonResponse
    {
        abort_unless($message->conversation()->where('user_id', $request->user()->id)->exists(), 404);

        return response()->json(['message' => $this->messageData($message->fresh())]);
    }

    public function clear(Request $request, OpenCodeService $openCode): JsonResponse
    {
        $conversations = $request->user()->chatbotConversations()->get();

        foreach ($conversations as $conversation) {
            if ($conversation->remote_session_id !== null) {
                $openCode->deleteSession($conversation->remote_session_id);
            }
        }

        $request->user()->chatbotConversations()->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @return array{id: int, role: string, content: string|null, status: string, error: string|null, created_at: string|null}
     */
    protected function messageData(ChatbotMessage $message): array
    {
        $content = $message->content;
        if ($content !== null) {
            $content = $message->role === 'assistant'
                ? $this->contentSanitizer->sanitizeOutput($content)
                : $this->contentSanitizer->sanitizeInput($content);
        }

        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $content,
            'status' => $message->status,
            'error' => $message->error_message !== null
                ? $this->contentSanitizer->sanitizeInput($message->error_message)
                : null,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}

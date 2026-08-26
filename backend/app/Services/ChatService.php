<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use OpenAI\Laravel\Facades\OpenAI;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\AiInteraction;

class ChatService
{
    public function send(string $message, ?int $conversationId = null): array
    {
        [$conversation, $history] = $this->getConversationAndHistory(
            $message,
            $conversationId
        );
        $startedAt = microtime(true);
        $response = OpenAI::responses()->create([
            'model' => 'gpt-5',
            'instructions' => file_get_contents(
                storage_path('eve/personality.txt')
            ),
            'input' => $history,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $response->outputText,
        ]);



        $this->saveInteraction(
            $conversation,
            $response->usage,
            $startedAt
        );

        return [
            'conversation_id' => $conversation->id,
            'reply' => $response->outputText,
        ];
    }

    public function stream(
        string $message,
        ?int $conversationId
    ): StreamedResponse
    {

        return response()->stream(function () use ($message, $conversationId) {

            [$conversation, $history] = $this->getConversationAndHistory(
                $message,
                $conversationId
            );
            $startedAt = microtime(true);
            $stream = OpenAI::responses()->createStreamed([
                'model' => 'gpt-5',
                'instructions' => file_get_contents(
                    storage_path('eve/personality.txt')
                ),
                'input' => $history,
            ]);

            $assistantReply = '';

            $finalResponse = null;

            foreach ($stream as $event) {

                switch ($event->event) {

                    case 'response.output_text.delta':

                        $delta = $event->response->delta;

                        $assistantReply .= $delta;

                        echo "data: " . json_encode([
                                'text' => $delta,
                            ]) . "\n\n";

                        @ob_flush();
                        flush();

                        break;

                    case 'response.completed':

                        $finalResponse = $event;

                        break;
                }
            }

            if ($finalResponse) {

                $this->saveInteraction(
                    $conversation,
                    $finalResponse->response->response->usage,
                    $startedAt
                );

            }

            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $assistantReply,
            ]);


            logger()->info(json_encode($finalResponse, JSON_PRETTY_PRINT));

            echo "data: " . json_encode([
                    'conversation_id' => $conversation->id,
                ]) . "\n\n";

            echo "event: done\n";
            echo "data: {}\n\n";

            @ob_flush();
            flush();

            return;

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
    /**
     * @return array{Conversation, array<int, array{role: string, content: string}>}
     */
    private function getConversationAndHistory(
        string $message,
        ?int $conversationId
    ): array
    {
        if ($conversationId) {
            $conversation = Conversation::findOrFail($conversationId);
        } else {
            $conversation = Conversation::create([
                'title' => mb_substr($message, 0, 40),
            ]);
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
        ]);

        $history = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => [
                'role' => $item->role,
                'content' => $item->content,
            ])
            ->toArray();

        return [$conversation, $history];
    }

    private function saveInteraction(
        Conversation $conversation,
        mixed $usage,
        float $startedAt,
        string $model = 'gpt-5'
    ): void
    {
        AiInteraction::create([
            'conversation_id' => $conversation->id,

            'provider' => 'openai',

            'model' => $model,

            'input_tokens' => $usage->inputTokens ?? 0,

            'output_tokens' => $usage->outputTokens ?? 0,

            'reasoning_tokens' => $usage->outputTokensDetails->reasoningTokens ?? 0,

            'cached_tokens' => $usage->inputTokensDetails->cachedTokens ?? 0,

            'total_tokens' => $usage->totalTokens ?? 0,

            'cost' => 0,

            'total_ms' => (int) ((microtime(true) - $startedAt) * 1000),

            'status' => 'completed',
        ]);
    }
}

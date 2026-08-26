<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class Eve
{
    public function chat(string $message): string
    {
        $response = OpenAI::responses()->create([
            'model' => 'gpt-5',
            'instructions' => $this->instructions(),
            'input' => $message,
        ]);

        return $response->outputText;
    }

    private function instructions(): string
    {
        return file_get_contents(storage_path('eve/personality.txt'));
    }
}

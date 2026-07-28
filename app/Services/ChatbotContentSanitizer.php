<?php

namespace App\Services;

class ChatbotContentSanitizer
{
    private const SAFE_ERROR = 'No pude generar una respuesta en un formato legible. Reformulá la consulta.';

    public function sanitizeInput(string $content): string
    {
        return $this->redactSensitiveData(trim($content));
    }

    public function sanitizeOutput(string $content): string
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*(.*?)\s*```$/isu', '$1', $content) ?? $content;

        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $this->extractTextFromJson($decoded) ?? self::SAFE_ERROR;
        }

        $content = $this->redactSensitiveData($content);
        $content = $this->redactModelInformation($content);
        $content = $this->redactInternalPaths($content);

        return mb_substr(trim($content), 0, 8000);
    }

    protected function extractTextFromJson(mixed $decoded): ?string
    {
        if (is_string($decoded)) {
            return $decoded;
        }

        if (!is_array($decoded)) {
            return null;
        }

        foreach (['respuesta', 'answer', 'content', 'message', 'text'] as $key) {
            $value = $decoded[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }

            if (is_array($value) && is_string($value['content'] ?? null)) {
                return $value['content'];
            }
        }

        return null;
    }

    protected function redactSensitiveData(string $content): string
    {
        $content = preg_replace(
            '/-----BEGIN [A-Z ]*PRIVATE KEY-----.*?-----END [A-Z ]*PRIVATE KEY-----/isu',
            '[CREDENCIAL OCULTA]',
            $content
        ) ?? $content;

        $content = preg_replace(
            '/\bAuthorization\s*:\s*(?:Basic|Bearer)\s+[^\s,;]+/iu',
            'Authorization: [CREDENCIAL OCULTA]',
            $content
        ) ?? $content;

        $content = preg_replace(
            '/\bBearer\s+[A-Za-z0-9._~+\/=\-]{8,}/iu',
            'Bearer [CREDENCIAL OCULTA]',
            $content
        ) ?? $content;

        $content = preg_replace_callback(
            '/\b(api[\s_-]?key|token|secret|password|contraseña|contrasena|clave)\b\s*(?:[:=]|\bes\b)\s*("[^"]*"|\'[^\']*\'|[^\s,;]+)/iu',
            fn (array $matches): string => $matches[1] . ': [CREDENCIAL OCULTA]',
            $content
        ) ?? $content;

        $content = preg_replace(
            '/\b(?:sk|pk|api|key|token)[-_][A-Za-z0-9._\-]{10,}\b/iu',
            '[CREDENCIAL OCULTA]',
            $content
        ) ?? $content;

        return preg_replace(
            '/(https?:\/\/[^\s:\/@]+):[^\s@]+@/iu',
            '$1:[CREDENCIAL OCULTA]@',
            $content
        ) ?? $content;
    }

    protected function redactModelInformation(string $content): string
    {
        $content = preg_replace(
            '/\b(?:opencode-go|opencode)\/[A-Za-z0-9._:\-]+\b/iu',
            '[MODELO INTERNO]',
            $content
        ) ?? $content;

        $content = preg_replace(
            '/\b(?:OpenCode Go|DeepSeek(?:[-\s][A-Za-z0-9.]+){0,3}|Laguna(?:[-\s][A-Za-z0-9.]+){0,3}|MiMo(?:[-\s][A-Za-z0-9.]+){0,3}|Qwen(?:[-\s][A-Za-z0-9.]+){0,3}|GLM(?:[-\s][A-Za-z0-9.]+){0,3}|MiniMax(?:[-\s][A-Za-z0-9.]+){0,3}|Kimi(?:[-\s][A-Za-z0-9.]+){0,3}|Grok(?:[-\s][A-Za-z0-9.]+){0,3}|Nemotron(?:[-\s][A-Za-z0-9.]+){0,3})\b/iu',
            '[MODELO INTERNO]',
            $content
        ) ?? $content;

        return $content;
    }

    protected function redactInternalPaths(string $content): string
    {
        $content = preg_replace(
            '~`?docs[\\/]sistema[\\/]*`?~iu',
            'la documentación aprobada',
            $content
        ) ?? $content;

        return preg_replace(
            '~\b[A-Z]:[^\r\n]*~iu',
            '[RUTA INTERNA]',
            $content
        ) ?? $content;
    }
}

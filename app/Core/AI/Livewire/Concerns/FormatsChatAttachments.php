<?php

namespace App\Core\AI\Livewire\Concerns;

use App\Core\AI\DTO\Message;

/**
 * Maps persisted chat attachments to the data consumed by the chat view.
 */
trait FormatsChatAttachments
{
    /**
     * @param  list<Message>  $messages
     * @return list<Message>
     */
    private function mapMessagesWithUi(array $messages): array
    {
        return array_map(function (Message $message): Message {
            $attachments = $message->getMetaArray('attachments', []);
            if ($attachments === []) {
                return $this->messageWithAttachmentsUi($message, []);
            }

            $mapped = array_map(fn ($att): ?array => $this->attachmentToUi($att), $attachments);

            return $this->messageWithAttachmentsUi($message, array_values(array_filter($mapped)));
        }, $messages);
    }

    /**
     * @param  array<string, mixed>  $attachment
     */
    private function attachmentUiUrl(array $attachment): string
    {
        $mime = is_string($attachment['mime_type'] ?? null) ? (string) $attachment['mime_type'] : 'application/octet-stream';
        $id = is_string($attachment['id'] ?? null) ? (string) $attachment['id'] : '';

        return route('ai.chat.attachments.show', [
            'employeeId' => $this->employeeId,
            'sessionId' => (string) $this->selectedSessionId,
            'attachmentId' => $id,
        ]).'?mime='.urlencode($mime);
    }

    /**
     * @return array{id: string, kind: string, mime_type: string, original_name: string, size: int|null, url: string}|null
     */
    private function attachmentToUi(mixed $attachment): ?array
    {
        if (! is_array($attachment)) {
            return null;
        }

        $id = $attachment['id'] ?? null;
        if (! is_string($id) || $id === '') {
            return null;
        }

        $kind = is_string($attachment['kind'] ?? null) ? (string) $attachment['kind'] : 'document';
        $mime = is_string($attachment['mime_type'] ?? null) ? (string) $attachment['mime_type'] : 'application/octet-stream';
        $name = is_string($attachment['original_name'] ?? null) ? (string) $attachment['original_name'] : $id;
        $size = is_int($attachment['size'] ?? null) ? (int) $attachment['size'] : null;

        return [
            'id' => $id,
            'kind' => $kind,
            'mime_type' => $mime,
            'original_name' => $name,
            'size' => $size,
            'url' => $this->attachmentUiUrl(['id' => $id, 'mime_type' => $mime]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $attachmentsUi
     */
    private function messageWithAttachmentsUi(Message $message, array $attachmentsUi): Message
    {
        return new Message(
            role: $message->role,
            content: $message->content,
            timestamp: $message->timestamp,
            runId: $message->runId,
            meta: array_merge($message->meta, ['attachments_ui' => $attachmentsUi]),
            type: $message->type,
        );
    }
}

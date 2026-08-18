<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageFileRepositoryInterface;

final class ForumMessageFileRepository implements ForumMessageFileRepositoryInterface
{
    public function attachFilesToMessage(int $messageId, array $fileIds): void
    {
        if ($fileIds === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($fileIds as $fileId) {
            $rows[] = [
                'message_id' => $messageId,
                'file_id'    => $fileId,
                'created_at' => $now,
            ];
        }

        Capsule::table('forum_message_files')->insertOrIgnore($rows);
    }

    public function getFileIdsByMessageId(int $messageId): array
    {
        return Capsule::table('forum_message_files')
            ->where('message_id', $messageId)
            ->pluck('file_id')
            ->all();
    }

    public function getFileIdsByTopicId(int $topicId): array
    {
        return Capsule::table('forum_message_files')
            ->join('forum_messages', 'forum_messages.id', '=', 'forum_message_files.message_id')
            ->where('forum_messages.topic_id', $topicId)
            ->distinct()
            ->pluck('forum_message_files.file_id')
            ->all();
    }

    public function deleteByMessageId(int $messageId): void
    {
        Capsule::table('forum_message_files')
            ->where('message_id', $messageId)
            ->delete();
    }

    public function deleteByTopicId(int $topicId): void
    {
        Capsule::table('forum_message_files')
            ->whereIn('message_id', static function ($query) use ($topicId): void {
                $query->from('forum_messages')
                    ->select('id')
                    ->where('topic_id', $topicId);
            })
            ->delete();
    }

    public function getOrphanedFileIds(array $fileIds): array
    {
        if ($fileIds === []) {
            return [];
        }

        $linkedIds = Capsule::table('forum_message_files')
            ->whereIn('file_id', $fileIds)
            ->distinct()
            ->pluck('file_id')
            ->all();

        return array_values(array_diff($fileIds, $linkedIds));
    }

    public function getOrphanStorageFileIds(string $pathPrefix, string $createdBefore, int $limit): array
    {
        return Capsule::table('files as f')
            ->leftJoin('forum_message_files as mf', 'mf.file_id', '=', 'f.id')
            ->whereNull('mf.file_id')
            ->where('f.path', 'like', $pathPrefix . '/%')
            ->where('f.created_at', '<=', $createdBefore)
            ->orderBy('f.id')
            ->limit(max(1, $limit))
            ->pluck('f.id')
            ->all();
    }
}

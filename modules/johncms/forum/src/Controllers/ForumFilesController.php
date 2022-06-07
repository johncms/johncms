<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Support\Collection;
use Johncms\FileInfo;
use Johncms\Forum\Models\ForumFile;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Http\Request;
use Johncms\Users\User;
use Psr\Http\Message\ResponseInterface;

class ForumFilesController extends BaseForumController
{
    public function add(int $messageId, Request $request, User $user): string|ResponseInterface
    {
        $extensions = new Collection(config('forum.extensions'));
        $maxFileSize = config('forum.settings.max_file_size', 10240);

        $this->metaTagManager->setAll(__('Add file'));

        $message = ForumMessage::query()->findOrFail($messageId);
        if ($message->user_id !== $user->id) {
            return $this->render->render(
                'system::pages/result',
                [
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => route('forum.index'),
                    'back_url_name' => __('Back'),
                ]
            );
        }

        // Check the time limit for file upload
        if ($message->date < (time() - 3600)) {
            return $this->render->render(
                'system::pages/result',
                [
                    'type'          => 'alert-danger',
                    'message'       => __('The time allotted for the file upload has expired'),
                    'back_url'      => $message->topic->last_page_url,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($request->getMethod() === 'POST') {
            // Обработка файла (если есть), проверка на ошибки
            $files = $request->getUploadedFiles();
            if (! empty($files) && ! empty($files['fail'])) {
                /** @var \GuzzleHttp\Psr7\UploadedFile $file */
                $file = $files['fail'];

                $file_info = new FileInfo($file->getClientFilename());
                $ext = strtolower($file_info->getExtension());

                $error = [];
                // Check file size
                if ($file->getSize() > 1024 * $maxFileSize) {
                    $error[] = __('File size exceed') . ' ' . $maxFileSize . 'kb.';
                }

                // Check allowed extensions
                $allExtensions = $extensions->flatten();
                if ($allExtensions->search($ext, true) === false) {
                    $error[] = __('The forbidden file format.<br>You can upload files of the following extension') . ':<br>' . $allExtensions->implode(', ');
                }

                $file_name = $file_info->getCleanName();

                // Проверка наличия файла с таким же именем
                if (file_exists(UPLOAD_PATH . 'forum/attach/' . $file_name)) {
                    $file_name = time() . $file_name;
                }

                // Save file
                if (! $error) {
                    $file->moveTo(UPLOAD_PATH . 'forum/attach/' . $file_name);
                    if (! $file->isMoved()) {
                        $error[] = __('Error uploading file');
                    }
                }

                if (! $error) {
                    // Определяем тип файла
                    if (in_array($ext, $extensions->get('windows'))) {
                        $type = 1;
                    } elseif (in_array($ext, $extensions->get('java'))) {
                        $type = 2;
                    } elseif (in_array($ext, $extensions->get('sis'))) {
                        $type = 3;
                    } elseif (in_array($ext, $extensions->get('documents'))) {
                        $type = 4;
                    } elseif (in_array($ext, $extensions->get('pictures'))) {
                        $type = 5;
                    } elseif (in_array($ext, $extensions->get('archives'))) {
                        $type = 6;
                    } elseif (in_array($ext, $extensions->get('video'))) {
                        $type = 7;
                    } elseif (in_array($ext, $extensions->get('audio'))) {
                        $type = 8;
                    } else {
                        $type = 9;
                    }

                    ForumFile::query()->create(
                        [
                            'cat'      => $message->topic->section->parent,
                            'subcat'   => $message->topic->section_id,
                            'topic'    => $message->topic_id,
                            'post'     => $messageId,
                            'time'     => $message->date,
                            'filename' => $file_name,
                            'filetype' => $type,
                        ]
                    );
                } else {
                    return $this->render->render(
                        'system::pages/result',
                        [
                            'type'          => 'alert-danger',
                            'message'       => $error,
                            'back_url'      => route('forum.addFile', ['messageId' => $messageId]),
                            'back_url_name' => __('Repeat'),
                        ]
                    );
                }
            } else {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'type'          => 'alert-danger',
                        'message'       => __('Error uploading file'),
                        'back_url'      => route('forum.addFile', ['messageId' => $messageId]),
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }
            $file_attached = true;
        }

        return $this->render->render(
            'forum::add_file',
            [
                'id'            => $messageId,
                'maxFileSize'   => $maxFileSize,
                'file_attached' => $file_attached ?? false,
                'topic_id'      => $message->topic_id,
                'back_url'      => $message->topic->last_page_url,
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ChangeTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetChangeTopicContextUseCase;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

final readonly class ChangeTopicController
{
    public function __construct(
        private ForumErrorRenderer $forumErrorRenderer,
        private GetChangeTopicContextUseCase $contextUseCase,
        private ChangeTopicUseCase $changeTopicUseCase,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $topic = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Change the topic'),
                    'page_title'    => __('Change the topic'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $formData = [
            'name'             => $request->body('name', (string) $topic->name),
            'meta_keywords'    => $request->body('meta_keywords', $topic->meta_keywords ?? ''),
            'meta_description' => $request->body('meta_description', $topic->meta_description ?? ''),
        ];

        $errors = [];
        if ($request->getMethod() === 'POST') {
            $rules = [
                'name'             => [
                    new StringLength(min: 3, max: 200),
                    new ModelNotExists(
                        model: ForumTopic::class,
                        field: 'name',
                        // Another topic of the same section may not carry this name; the topic
                        // being edited is allowed to keep its own.
                        exclude: static function ($query) use ($topic, $id): void {
                            $query->where('section_id', $topic->section_id)
                                ->where('id', '!=', $id);
                        },
                    ),
                ],
                // The meta fields are optional: only their length is capped.
                'meta_keywords'    => [new StringLength(max: 250, allowEmpty: true)],
                'meta_description' => [new StringLength(max: 65000, allowEmpty: true)],
            ];

            $result = $this->validator->validate($formData, $rules);
            if ($result->isValid()) {
                $this->changeTopicUseCase->execute(
                    topic:           $topic,
                    name:            $formData['name'],
                    metaKeywords:    $formData['meta_keywords'],
                    metaDescription: $formData['meta_description'],
                );

                redirect($topic->url);
            }

            $errors = $result->getErrors();
        }

        return new ViewResponse(
            '@forum/public/change-topic.twig',
            [
                'title'      => __('Change the topic'),
                'page_title' => __('Change the topic'),
                'action_url' => '/forum/change-topic/' . $topic->id . '/',
                'form_data'  => $formData,
                'back_url'   => $topic->url,
                'errors'     => $errors,
            ]
        );
    }
}

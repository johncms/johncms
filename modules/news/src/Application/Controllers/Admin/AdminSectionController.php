<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\Logs\DebugDetailsPolicy;
use Johncms\Modules\News\Application\Section;
use Johncms\Modules\News\Application\Utils\Helpers;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Http\Session;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class AdminSectionController
{
    public function __construct(
        private NavChain $navChain,
        private ExceptionResponseFactory $exceptionResponses,
        private DebugDetailsPolicy $debugDetailsPolicy,
        private LoggerInterface $logger,
        private Session $session,
    ) {
    }

    /**
     * The links every page of the news section sits under. Added by the page rather than by
     * the constructor: the chain belongs to the request being served.
     */
    private function addSectionBreadcrumbs(): void
    {
        $this->navChain->add(__('News'), '/admin/news/');
        $this->navChain->add(__('Section list'), '/admin/news/content/');
    }

    /**
     * Section creation page
     *
     * @param Request $request
     * @param Section $section_service
     * @param int $section_id
     * @return Response
     */
    public function add(Request $request, Section $section_service, int $section_id = 0): Response | ViewResponse
    {
        $this->addSectionBreadcrumbs();

        $pageTitle = __('Create section');

        if (! empty($section_id)) {
            try {
                $current_section = (new NewsSection())->findOrFail($section_id);

                Helpers::buildAdminBreadcrumbs($current_section->parentSection);

                // Adding the current section to the navigation chain
                $this->navChain->add($current_section->name, '/admin/news/content/' . $current_section->id);
            } catch (ModelNotFoundException $exception) {
                pageNotFound();
            }
        }

        $this->navChain->add(__('Create section'));

        $data = [
            'action_url' => '/admin/news/add_section/' . $section_id,
            'back_url'   => '/admin/news/content/' . $section_id,
            'section_id' => $section_id,
            'fields'     => [
                'parent'      => $section_id,
                'name'        => $request->body('name', ''),
                'code'        => $request->body('code', ''),
                'keywords'    => $request->body('keywords', ''),
                'description' => $request->body('description', ''),
                'text'        => $request->body('text', ''),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);

        $errors = [];
        // Processing the sent data from the form.
        if ($request->getMethod() === 'POST') {
            if (empty($data['fields']['name'])) {
                $errors[] = __('The section name cannot be empty');
            }

            // Code generation
            if (empty($data['fields']['code'])) {
                $data['fields']['code'] = Str::slug($data['fields']['name']);
            } else {
                $data['fields']['code'] = Str::slug($data['fields']['code']);
            }

            if (empty($errors)) {
                if (! empty($section_id)) {
                    $check = (new NewsSection())
                        ->where('code', $data['fields']['code'])
                        ->where('parent', $section_id)
                        ->first();
                } else {
                    $check = (new NewsSection())
                        ->where('code', $data['fields']['code'])
                        ->whereNull('parent')
                        ->first();
                }

                if (! $check) {
                    (new NewsSection())->create($data['fields']);
                    $section_service->clearCache();
                    $this->session->flash('success_message', __('The section was created successfully'));
                    return new RedirectResponse('/admin/news/content/' . $section_id);
                }
                $errors[] = __('A section with this code already exists');
            }
        }

        $data['errors'] = $errors;

        return new ViewResponse('@news/admin/section-form.twig', $this->menu($pageTitle) + $data);
    }

    /**
     * The edit section page
     *
     * @param int $section_id
     * @param Request $request
     * @return Response
     */
    public function edit(int $section_id, Request $request): Response | ViewResponse
    {
        $this->addSectionBreadcrumbs();

        $this->navChain->add(__('Edit section'));
        $pageTitle = __('Edit section');

        try {
            $section = (new NewsSection())->findOrFail($section_id);
            Helpers::buildAdminBreadcrumbs($section->parentSection);
        } catch (ModelNotFoundException $exception) {
            pageNotFound();
        }

        $data = [
            'action_url' => '/admin/news/edit_section/' . $section_id,
            'back_url'   => '/admin/news/content/' . $section->parent,
            'section_id' => $section_id,
            'fields'     => [
                'name'        => $request->body('name', (string) $section->name),
                'code'        => $request->body('code', (string) $section->code),
                'keywords'    => $request->body('keywords', (string) $section->keywords),
                'description' => $request->body('description', (string) $section->description),
                'text'        => $request->body('text', (string) $section->text),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);

        $errors = [];
        // Processing the sent data from the form.
        if ($request->getMethod() === 'POST') {
            if (empty($data['fields']['name'])) {
                $errors[] = __('The section name cannot be empty');
            }

            // Code generation
            if (empty($data['fields']['code'])) {
                $data['fields']['code'] = Str::slug($data['fields']['name']);
            } else {
                $data['fields']['code'] = Str::slug($data['fields']['code']);
            }

            if (empty($errors)) {
                $check = (new NewsSection())
                    ->where('code', $data['fields']['code'])
                    ->where('id', '!=', $section_id)
                    ->where('parent', '=', $section->parent)
                    ->first();

                if (! $check) {
                    $section->update($data['fields']);
                    $this->session->flash('success_message', __('The section was updated successfully'));
                    return new RedirectResponse('/admin/news/content/' . $section->parent);
                }
                $errors[] = __('A section with this code already exists');
            }
        }

        $data['errors'] = $errors;

        return new ViewResponse('@news/admin/section-form.twig', $this->menu($pageTitle) + $data);
    }

    /**
     * Delete section
     *
     * @param int $section_id
     * @param Request $request
     * @param Section $section_service
     * @return Response
     * @throws Exception
     */
    public function del(int $section_id, Request $request, Section $section_service): Response | ViewResponse
    {
        $this->addSectionBreadcrumbs();

        $data = [];
        // Get the section to delete
        try {
            $section = (new NewsSection())->findOrFail($section_id);
        } catch (ModelNotFoundException $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            return $this->exceptionResponses->internalServerError($exception, $this->debugDetailsPolicy->allowed());
        }

        $post = $request->request->all();
        $sessionToken = $this->session->get('delete_token');

        // Checking the data and deleting the section
        if (
            isset($post['delete_token'], $sessionToken) &&
            $sessionToken === $post['delete_token'] &&
            $request->getMethod() === 'POST'
        ) {
            $children_sections = $section_service->getCachedSubsections($section);
            $section_service->clearCache();

            // Delete articles
            (new NewsArticle())->whereIn('section_id', $children_sections)->delete();

            // Delete subsections
            (new NewsSection())->whereIn('id', $children_sections)->delete();

            $this->session->flash('success_message', __('The section was successfully deleted'));
            return new RedirectResponse('/admin/news/content/' . $section->parent);
        }

        $data['section'] = $section;

        // Generate the token
        $data['delete_token'] = uniqid('', true);
        $this->session->set('delete_token', $data['delete_token']);

        $data['action_url'] = '/admin/news/del_section/' . $section_id;

        return new ViewResponse(
            '@news/admin/delete-confirm.twig',
            $data + $this->menu(__('News')) + ['section' => null, 'article' => null]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title): array
    {
        return [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['news' => true],
        ];
    }
}

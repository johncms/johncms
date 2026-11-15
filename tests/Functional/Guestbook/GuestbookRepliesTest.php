<?php

declare(strict_types=1);

namespace Tests\Functional\Guestbook;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Guestbook\Application\Services\GuestbookReplyNotifier;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * An entry answers another one: the answer recalls it with a link, and its author is told.
 */
final class GuestbookRepliesTest extends FunctionalTestCase
{
    public function testAnAnswerRecallsTheEntryAndItsAuthorIsTold(): void
    {
        $answered = FunctionalUserFactory::create();
        $entry = Fixture::insert('guest', ['adm' => 0, 'user_id' => $answered->id, 'name' => $answered->name, 'text' => '<p>The first words</p>', 'time' => time() - 60]);
        $author = FunctionalUserFactory::create();

        $response = $this->postForm('/guestbook/', ['message' => '<p>An answer</p>', 'reply_to' => (string) $entry], $author);
        self::assertSame(302, $response->getStatusCode());

        $answer = GuestbookEntry::query()->where('user_id', $author->id)->firstOrFail();
        self::assertSame($entry, $answer->reply_to_id);

        $notification = Capsule::table('notifications')->where('user_id', $answered->id)->first();
        self::assertNotNull($notification);
        self::assertSame(GuestbookReplyNotifier::MODULE, $notification->module);
        self::assertSame(GuestbookReplyNotifier::EVENT, $notification->event_type);
        self::assertSame($author->id, (int) $notification->sender_id);
        self::assertSame($answer->id, (int) $notification->entity_id);
        $fields = json_decode((string) $notification->fields, true);
        self::assertSame('/guestbook/entry/' . $answer->id, $fields['reply_url']);
        self::assertSame('An answer', $fields['message']);

        $page = HTMLDocument::createFromString((string) $this->handleRequest('/guestbook/')->getContent(), LIBXML_NOERROR);
        $link = $page->querySelector('#guestbook-entry-' . $answer->id . ' a.site-comment__reply-to');
        self::assertNotNull($link);
        self::assertSame('/guestbook/entry/' . $entry, $link->getAttribute('href'));
        self::assertSame((string) $entry, $link->getAttribute('data-comment-reply-to'));
        self::assertStringContainsString('The first words', $link->textContent);
    }

    public function testTheNotificationSaysWhoAnsweredAndLeadsToTheAnswer(): void
    {
        $answered = FunctionalUserFactory::create();
        $entry = Fixture::insert('guest', ['adm' => 0, 'user_id' => $answered->id, 'name' => $answered->name, 'text' => '<p>Question</p>', 'time' => time() - 60]);
        $author = FunctionalUserFactory::create();
        $this->postForm('/guestbook/', ['message' => '<p>An answer</p>', 'reply_to' => (string) $entry], $author);
        $answer = GuestbookEntry::query()->where('user_id', $author->id)->firstOrFail();

        $html = (string) $this->handleRequest('/notifications/', cookies: $this->actingAs($answered))->getContent();
        self::assertStringContainsString('href="/guestbook/entry/' . $answer->id . '"', $html);
        self::assertStringContainsString($author->name, $html);
    }

    public function testAGuestIsHeardWithoutASender(): void
    {
        $answered = FunctionalUserFactory::create();
        $entry = Fixture::insert('guest', ['adm' => 0, 'user_id' => $answered->id, 'name' => $answered->name, 'text' => '<p>Question</p>', 'time' => time() - 60]);
        $answer = Fixture::insert('guest', ['adm' => 0, 'user_id' => 0, 'name' => 'Visitor & Co', 'text' => '<p>From a guest</p>', 'time' => time(), 'reply_to_id' => $entry]);

        $this->notifier()->replied(GuestbookEntry::query()->findOrFail($entry), GuestbookEntry::query()->findOrFail($answer));

        $notification = Capsule::table('notifications')->where('user_id', $answered->id)->first();
        self::assertNotNull($notification);
        self::assertSame(0, (int) $notification->sender_id);
        self::assertSame('Visitor & Co', json_decode((string) $notification->fields, true)['user_name'], 'The name is stored as written; the template escapes it.');
    }

    public function testNobodyIsToldOfTheirOwnAnswerAndAGuestIsToldNothing(): void
    {
        $author = FunctionalUserFactory::create();
        $own = Fixture::insert('guest', ['adm' => 0, 'user_id' => $author->id, 'name' => $author->name, 'text' => '<p>Mine</p>', 'time' => time() - 60]);
        $guest = Fixture::insert('guest', ['adm' => 0, 'user_id' => 0, 'name' => 'Visitor', 'text' => '<p>Of a guest</p>', 'time' => time() - 60]);

        $this->postForm('/guestbook/', ['message' => '<p>To myself</p>', 'reply_to' => (string) $own], $author);
        $this->postForm('/guestbook/', ['message' => '<p>To the guest</p>', 'reply_to' => (string) $guest], FunctionalUserFactory::create());

        self::assertSame(2, GuestbookEntry::query()->whereIn('reply_to_id', [$own, $guest])->count(), 'Both answers recall their entries.');
        self::assertSame(0, Capsule::table('notifications')->count());
    }

    public function testAnEntryOfTheOtherGuestbookIsNotAnswered(): void
    {
        $answered = FunctionalUserFactory::createSupervisor();
        $entry = Fixture::insert('guest', ['adm' => 1, 'user_id' => $answered->id, 'name' => $answered->name, 'text' => '<p>Of the club</p>', 'time' => time() - 60]);
        $author = FunctionalUserFactory::create();

        $this->postForm('/guestbook/', ['message' => '<p>Trying</p>', 'reply_to' => (string) $entry], $author);

        self::assertNull(GuestbookEntry::query()->where('user_id', $author->id)->firstOrFail()->reply_to_id);
        self::assertSame(0, Capsule::table('notifications')->count());
    }

    public function testDeletingTheAnswerTakesTheNotificationBack(): void
    {
        $answered = FunctionalUserFactory::create();
        $entry = Fixture::insert('guest', ['adm' => 0, 'user_id' => $answered->id, 'name' => $answered->name, 'text' => '<p>Question</p>', 'time' => time() - 60]);
        $this->postForm('/guestbook/', ['message' => '<p>An answer</p>', 'reply_to' => (string) $entry], FunctionalUserFactory::create());
        $answer = GuestbookEntry::query()->where('reply_to_id', $entry)->firstOrFail();
        self::assertSame(1, Capsule::table('notifications')->count());

        $this->postForm('/guestbook/delpost', ['id' => (string) $answer->id, 'confirmed' => '1'], FunctionalUserFactory::createSupervisor());

        self::assertSame(0, Capsule::table('notifications')->count());
    }

    public function testAFormSentBackWithAnErrorStillAnswersTheEntry(): void
    {
        $answered = FunctionalUserFactory::create();
        $entry = Fixture::insert('guest', ['adm' => 0, 'user_id' => $answered->id, 'name' => $answered->name, 'text' => '<p>The first words</p>', 'time' => time()]);
        $club = Fixture::insert('guest', ['adm' => 1, 'user_id' => $answered->id, 'name' => $answered->name, 'text' => '<p>Of the club</p>', 'time' => time()]);
        $author = FunctionalUserFactory::create();

        $form = $this->sentBack($author, $entry);
        self::assertSame((string) $entry, $form->querySelector('[data-comment-reply-field]')?->getAttribute('value'));
        $label = $form->querySelector('[data-comment-reply-label]');
        self::assertNotNull($label);
        self::assertFalse($label->hasAttribute('hidden'));
        self::assertSame($answered->name, $label->querySelector('[data-comment-reply-name]')?->textContent);
        self::assertSame('The first words', $label->querySelector('[data-comment-reply-excerpt]')?->textContent);

        $form = $this->sentBack($author, $club);
        self::assertSame('', $form->querySelector('[data-comment-reply-field]')?->getAttribute('value'), 'An entry of the other guestbook is not recalled.');
        self::assertTrue($form->querySelector('[data-comment-reply-label]')?->hasAttribute('hidden'));
    }

    public function testAnAnswerToADeletedEntryIsShownWithoutTheLink(): void
    {
        $answer = Fixture::insert('guest', ['adm' => 0, 'user_id' => 0, 'name' => 'Visitor', 'text' => '<p>Orphan</p>', 'time' => time(), 'reply_to_id' => 999999]);

        $page = HTMLDocument::createFromString((string) $this->handleRequest('/guestbook/')->getContent(), LIBXML_NOERROR);
        self::assertNotNull($page->querySelector('#guestbook-entry-' . $answer));
        self::assertNull($page->querySelector('#guestbook-entry-' . $answer . ' .site-comment__reply-to'));
    }

    /** The form of the page answered to an empty text sent without the script. */
    private function sentBack(User $author, int $replyTo): Element
    {
        $response = $this->postForm('/guestbook/', ['message' => '', 'reply_to' => (string) $replyTo], $author);
        self::assertSame(200, $response->getStatusCode());
        $form = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR)->querySelector('[data-guestbook-form]');
        self::assertNotNull($form);

        return $form;
    }

    private function notifier(): GuestbookReplyNotifier
    {
        $notifier = $this->container()->get(GuestbookReplyNotifier::class);
        self::assertInstanceOf(GuestbookReplyNotifier::class, $notifier);

        return $notifier;
    }
}

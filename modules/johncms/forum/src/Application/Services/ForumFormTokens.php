<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Http\Session;

/**
 * One-time tokens of the forms that write: a new topic, a new post, an answer and an edit. A form
 * sent twice — a second click, a reload of the answer — finds its token spent and saves nothing
 * the second time.
 *
 * The last few tokens issued stay valid together, so forms open in several tabs each keep theirs.
 */
final readonly class ForumFormTokens
{
    private const string KEY = 'forum.form_tokens';

    /** How many forms may be open at once; the oldest token goes first. */
    private const int KEPT = 10;

    public function __construct(private Session $session)
    {
    }

    public function issue(): string
    {
        $token = bin2hex(random_bytes(16));
        $this->session->set(self::KEY, array_slice([...$this->issued(), $token], -self::KEPT));

        return $token;
    }

    public function isValid(string $token): bool
    {
        return $token !== '' && in_array($token, $this->issued(), true);
    }

    /** Spends the token; false when it was never issued or is spent already. */
    public function consume(string $token): bool
    {
        if (! $this->isValid($token)) {
            return false;
        }

        $this->session->set(self::KEY, array_values(array_filter(
            $this->issued(),
            static fn (string $issued): bool => $issued !== $token
        )));

        return true;
    }

    /**
     * @return list<string>
     */
    private function issued(): array
    {
        $issued = $this->session->get(self::KEY, []);

        return is_array($issued) ? array_values(array_filter($issued, is_string(...))) : [];
    }
}

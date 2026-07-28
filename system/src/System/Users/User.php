<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Users;

#[\AllowDynamicProperties]
class User extends AbstractUserProperties
{
    public function __construct(array $properties = [])
    {
        $this->setProperties($properties);
    }

    /**
     * Replaces the whole state of the object with the given properties.
     *
     * The service holding the current user is shared, so the visitor of the next request is
     * loaded into the same instance instead of a new one being built for them (the constructors
     * of the controllers keep a reference to this object and would otherwise answer with the
     * previous visitor). Everything the previous visitor left behind has to go: declared
     * properties are put back to their default, dynamic ones — the columns this class does not
     * declare — are removed altogether.
     *
     * @param array<string, mixed> $properties
     */
    public function setProperties(array $properties): void
    {
        $defaults = get_class_vars(static::class);

        foreach (get_object_vars($this) as $property => $value) {
            if (array_key_exists($property, $defaults)) {
                $this->$property = $defaults[$property];
            } else {
                unset($this->$property);
            }
        }

        foreach ($properties as $key => $value) {
            $this->$key = $value;
        }

        $this->config = new UserConfig($this->set_user);
    }

    public function isValid(): bool
    {
        $isEmailConfirmationEnabled = config('johncms.user_email_confirmation');
        return ($this->id > 0 && $this->preg == 1 && (empty($isEmailConfirmationEnabled) || $this->email_confirmed == 1));
    }
}

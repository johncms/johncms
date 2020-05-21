<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Validator;

use Illuminate\Support\Arr;
use Johncms\Validator\Rules\ModelExists;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Barcode;
use Laminas\Validator\Between;
use Laminas\Validator\Callback;
use Laminas\Validator\CreditCard;
use Laminas\Validator\Date;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Explode;
use Laminas\Validator\File\Count;
use Laminas\Validator\File\Crc32;
use Laminas\Validator\File\ExcludeExtension;
use Laminas\Validator\File\ExcludeMimeType;
use Laminas\Validator\File\Exists;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\FilesSize;
use Laminas\Validator\File\Hash;
use Laminas\Validator\File\ImageSize;
use Laminas\Validator\File\IsCompressed;
use Laminas\Validator\File\IsImage;
use Laminas\Validator\File\Md5;
use Laminas\Validator\File\MimeType;
use Laminas\Validator\File\NotExists;
use Laminas\Validator\File\Sha1;
use Laminas\Validator\File\Size;
use Laminas\Validator\File\Upload;
use Laminas\Validator\File\UploadFile;
use Laminas\Validator\File\WordCount;
use Laminas\Validator\GreaterThan;
use Laminas\Validator\Hex;
use Laminas\Validator\Hostname;
use Laminas\Validator\Iban;
use Laminas\Validator\Identical;
use Laminas\Validator\InArray;
use Laminas\Validator\Ip;
use Laminas\Validator\Isbn;
use Laminas\Validator\IsCountable;
use Laminas\Validator\IsInstanceOf;
use Laminas\Validator\LessThan;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\Step;
use Laminas\Validator\StringLength;
use Laminas\Validator\Timezone;
use Laminas\Validator\Uri;
use Laminas\Validator\Uuid;
use Laminas\Validator\ValidatorChain;

class Validator
{
    protected $rules = [
        'Barcode'          => Barcode::class,
        'Between'          => Between::class,
        'Callback'         => Callback::class,
        'CreditCard'       => CreditCard::class,
        'Date'             => Date::class,
        'EmailAddress'     => EmailAddress::class,
        'Explode'          => Explode::class,
        'GreaterThan'      => GreaterThan::class,
        'Hex'              => Hex::class,
        'Hostname'         => Hostname::class,
        'Iban'             => Iban::class,
        'Identical'        => Identical::class,
        'InArray'          => InArray::class,
        'Ip'               => Ip::class,
        'Isbn'             => Isbn::class,
        'IsCountable'      => IsCountable::class,
        'IsInstanceOf'     => IsInstanceOf::class,
        'LessThan'         => LessThan::class,
        'NotEmpty'         => NotEmpty::class,
        'Regex'            => Regex::class,
        'Step'             => Step::class,
        'StringLength'     => StringLength::class,
        'Timezone'         => Timezone::class,
        'Uri'              => Uri::class,
        'Uuid'             => Uuid::class,
        'FilesCount'       => Count::class,
        'Crc32'            => Crc32::class,
        'ExcludeExtension' => ExcludeExtension::class,
        'ExcludeMimeType'  => ExcludeMimeType::class,
        'Exists'           => Exists::class,
        'Extension'        => Extension::class,
        'FilesSize'        => FilesSize::class,
        'Hash'             => Hash::class,
        'ImageSize'        => ImageSize::class,
        'IsCompressed'     => IsCompressed::class,
        'IsImage'          => IsImage::class,
        'Md5'              => Md5::class,
        'MimeType'         => MimeType::class,
        'NotExists'        => NotExists::class,
        'Sha1'             => Sha1::class,
        'Size'             => Size::class,
        'Upload'           => Upload::class,
        'UploadFile'       => UploadFile::class,
        'WordCount'        => WordCount::class,
        'ModelExists'      => ModelExists::class,
    ];

    private $errors = [];

    private $messages;

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->messages = $messages;
        $this->validate($data, $rules);
    }

    /**
     * Processing of validation rules
     *
     * @param array $data
     * @param array $rules
     */
    private function validate(array $data, array $rules): void
    {
        foreach ($rules as $field => $rule) {
            $value = Arr::get($data, $field, null);
            if (! empty($rule)) {
                $validator_chain = new ValidatorChain();
                foreach ($rule as $name => $options) {
                    if (
                        (is_array($options) && ! array_key_exists($name, $this->rules)) ||
                        (! is_array($options) && ! array_key_exists($options, $this->rules))
                    ) {
                        continue;
                    }
                    if (! is_array($options)) {
                        $rule_object = new $this->rules[$options]();
                    } else {
                        $rule_object = new $this->rules[$name]($options);
                    }

                    if (array_key_exists($name, $this->messages) && ! empty($this->messages[$name])) {
                        foreach ($this->messages[$name] as $key => $message) {
                            /** @var AbstractValidator $rule_object */
                            $rule_object->setMessage($message, $key);
                        }
                    }

                    $validator_chain->attach($rule_object);
                }
                if (! $validator_chain->isValid($value)) {
                    $this->errors[$field] = $validator_chain->getMessages();
                }
            }
        }
    }

    /**
     * The result of validation
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

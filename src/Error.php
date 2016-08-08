<?php declare(strict_types = 1);

namespace Lib24X;

abstract class Error
{
    const AUTH_FAILURE = 1;
    const INVALID_SENDER = 2;
    const INVALID_MESSAGE = 3;
    const INSUFFICIENT_CREDIT = 4;
    const INVALID_DATE = 5;
    const DUPLICATE_PHONEBOOK_NAME = 7;
    const EMPTY_PHONEBOOK = 8;
    const NON_EXISTENT_PHONEBOOK = 9;
    const DATEFROM_BAD_FORMAT = 10;
    const DATETO_BAD_FORMAT = 11;

    private static $errorStrings = [
        self::AUTH_FAILURE => 'Authentication details are invalid',
        self::INVALID_SENDER => 'Invalid sender specified',
        self::INVALID_MESSAGE => 'No message specified',
        self::INSUFFICIENT_CREDIT => 'Insufficient credit on account to send the requested number of messages',
        self::INVALID_DATE => 'Date to send greater than 1 year in advance',
        self::DUPLICATE_PHONEBOOK_NAME => 'The specified phonebook name already exists',
        self::EMPTY_PHONEBOOK => 'The specified phonebook is empty',
        self::NON_EXISTENT_PHONEBOOK => 'The specified phonebook does not exist',
        self::DATEFROM_BAD_FORMAT => 'Start date is not correctly formatted',
        self::DATETO_BAD_FORMAT => 'End date is not correctly formatted',
    ];

    public static final function getString(int $error): string
    {
        return self::$errorStrings[$error] ?? 'Unknown error ' . $error;
    }
}

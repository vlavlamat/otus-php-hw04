<?php

declare(strict_types=1);

namespace App\Services;

use App\Validator\BracketValidator;

class BracketValidationService
{
    private BracketValidator $bracketValidator;
    /**
     * Фабричный метод для создания сервиса с валидатором по умолчанию
     */
    public static function createDefault(): BracketValidationService
    {
        return new self(BracketValidator::createDefault());
    }
}
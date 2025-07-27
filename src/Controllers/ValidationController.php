<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Validator\BracketValidator;

class ValidationController
{
    private BracketValidator $validationService;

    /**
     * Фабричный метод для создания контроллера с зависимостями по умолчанию
     */
    public static function createDefault(): ValidationController
    {
        return new self(
            BracketValidator::createDefault(),
        );
    }

}
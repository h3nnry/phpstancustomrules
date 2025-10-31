<?php

namespace App\Core\Support\PhpStan;

use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;

class PhpStanRuleHelper
{
    public static function isEloquentModel(string $className): bool
    {
        return is_subclass_of($className, Model::class);
    }

    public static function shouldSkipClassForModel(string $className): bool
    {
        return preg_match('/Repository/', $className);
    }

    public static function returnErrorArray(
        string $errorMessage,
        Node $node
    ): array {
        return [
            RuleErrorBuilder::message($errorMessage)
                ->line($node->getLine())
                ->identifier('brezzels.noModelsOutsideRepos')
                ->build()
        ];
    }
}

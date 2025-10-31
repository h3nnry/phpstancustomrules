<?php

namespace App\Core\Support\PhpStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;

class PhpStanNoModelsOutsideReposRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        $currentClass = $classReflection->getName();

        if (PhpStanRuleHelper::shouldSkipClassForModel($currentClass)) {
            return [];
        }

        if ($node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name) {
            $callerClass = $scope->resolveName($node->class);
            return $this->getErrorsIfIsModelInstance($callerClass, $currentClass, $node);
        }

        if (!($node->var instanceof Node\Expr)) {
            return [];
        }

        $callerType = $scope->getType($node->var);
        if (!($callerType instanceof ObjectType)) {
            return [];
        }

        $callerClass = $callerType->getClassName();
        return $this->getErrorsIfIsModelInstance($callerClass, $currentClass, $node);
    }

    private function getErrorsIfIsModelInstance(string $callerClass, string $currentClass, Node $node): array
    {
        if (!PhpStanRuleHelper::isEloquentModel($callerClass)) {
            return [];
        }

        $errorMessage = sprintf(
            'Model %s used outside of a repository in class %s.',
            $callerClass,
            $currentClass
        );
        return PhpStanRuleHelper::returnErrorArray($errorMessage, $node);
    }
}

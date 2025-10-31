<?php

namespace App\Core\Support\PhpStan;

use Illuminate\Database\Eloquent\Relations\Relation;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;
use ReflectionClass;
use Exception;

class PhpStanNoModelsRelationsOutsideReposRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\PropertyFetch::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        $currentClass = $classReflection->getName();

        if (PhpStanRuleHelper::shouldSkipClassForModel($currentClass)) {
            return [];
        }

        $objectType = $scope->getType($node->var);
        if (!($objectType instanceof ObjectType)) {
            return [];
        }

        $objectClassName = $objectType->getClassName();


        if (!PhpStanRuleHelper::isEloquentModel($objectClassName)) {
            return [];
        }

        $propertyName = (string) $node->name;

        if ($propertyName === '') {
            return [];
        }

        if (!$this->isRelationshipProperty($objectClassName, $propertyName)) {
            return [];
        }

        $errorMessage = sprintf(
            'Model %s used outside of a repository in class %s.',
            $objectClassName,
            $currentClass
        );
        return PhpStanRuleHelper::returnErrorArray($errorMessage, $node);
    }

    private function isRelationshipProperty(string $className, string $methodName): bool
    {
        try {
            $reflectionClass = new ReflectionClass($className);

            if (! $reflectionClass->hasMethod($methodName)) {
                return false;
            }

            $method = $reflectionClass->getMethod($methodName);

            $returnType = $method->getReturnType()?->getName();

            if ($returnType === null) {
                return false;
            }

            return is_subclass_of($returnType, Relation::class);
        } catch (Exception $e) {
            return false;
        }
    }
}

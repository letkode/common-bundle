<?php

declare(strict_types=1);

namespace Letkode\CommonBundle\ValueResolver;

use Letkode\CommonBundle\Attribute\Mapping\MapUuid;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AutoconfigureTag('controller.argument_value_resolver', ['priority' => 150])]
final readonly class UuidValueResolver implements ValueResolverInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        /** @var MapUuid|null $attribute */
        $attribute = $argument->getAttributesOfType(MapUuid::class)[0] ?? null;

        if (null === $attribute || Uuid::class !== $argument->getType()) {
            return [];
        }

        $value = $request->attributes->get($argument->getName());

        if (!\is_string($value) || !Uuid::isValid($value)) {
            throw new BadRequestHttpException($this->translator->trans($attribute->invalidKey, domain: $attribute->domain));
        }

        return [Uuid::fromString($value)];
    }
}

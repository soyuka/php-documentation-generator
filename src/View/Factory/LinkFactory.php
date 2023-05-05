<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpDocumentGenerator\View\Factory;

use PhpDocumentGenerator\Link\LinkContext;
use PhpDocumentGenerator\ReflectionNamedType as PDGReflectionNamedType;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Symfony\Component\Filesystem\Path;

final class LinkFactory
{
    /**
     * @return [string, string[]]
     */
    public function getLinksFromPHPDoc(string $phpDoc): array
    {
        $links = [];

        // convert "@see" tags to link when possible
        if (preg_match_all('/{@(?:see|link) ([^}]+)}/', $phpDoc, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $value = $matches[1][$i];
                $phpDoc = str_replace($match, $value, $phpDoc);

                // HTTP link
                if (str_starts_with($value, 'https://') || str_starts_with($value, 'http://')) {
                    $links[] = $value;
                    continue;
                }

                continue;
                // TODO: We get here when there's an outside link {@link FooBar} for example GraphQl\ItemNormalizer method getAllowedAttributes
                // there's a bad design conception here as we should have a Context (reflection class) when we look for relative links
                // dd('what', $value);
                // $links[] = $this->getLink($value);
            }
        }

        return [$phpDoc, $links];
    }

    // TODO: doesn't handle exclusions
    public function createClassLink(\ReflectionClass $refl, LinkContext $linkContext): ?string
    {
        $name = $refl->getName();

        // PHP
        if (!$refl->isUserDefined()) {
            return sprintf('https://php.net/class.%s', strtolower($name));
        }

        // internal
        if (str_starts_with($name, $linkContext->namespace.'\\')) {
            // calling isExcluded to ensure the target class is not ignored
            // from references generation because the target reference file may not exist yet
            // TODO: check if that class is excluded
            // if (!$this->isExcluded($data, $this->configuration->references->tagsToIgnore, $this->configuration->references->exclude)) {
            return Path::join($linkContext->baseUrl, str_replace('.php', '', Path::makeRelative($refl->getFileName(), $linkContext->root)));
        }

        return null;
    }

    public function createTypeLink(\ReflectionType $refl, LinkContext $linkContext): ?string
    {
        if ($refl instanceof \ReflectionType && $refl->isBuiltin()) {
            return sprintf('https://php.net/%s', (string) $refl);
        }

        if ($refl instanceof \ReflectionNamedType && (class_exists($refl->getName()) || interface_exists($refl->getName()))) {
            return $this->createClassLink(new \ReflectionClass($refl->getName()), $linkContext);
        }

        return null;
    }

    public function createNodeLink(TypeNode $node, LinkContext $linkContext): ?string
    {
        $type = $node->__toString();

        if (class_exists($type) || interface_exists($type)) {
            return $this->createClassLink(new \ReflectionClass($type), $linkContext);
        }

        return $this->createTypeLink(new PDGReflectionNamedType($type, isBuiltin: !$node instanceof UnionTypeNode), $linkContext);
    }
}
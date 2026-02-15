<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\DevTools\PhpDocApiSurfaceAudit;

final class Auditor
{
    /** @return list<string> */
    public function audit(Config $config): array
    {
        $files = $this->getSourcePhpFiles($config);
        $symbols = $this->loadProjectSymbols($files);

        $missing = [];
        $tagIndexCache = [];

        foreach ($symbols as $className) {
            if ($this->isExcludedSymbol($className, $config->excludeSymbols)) {
                continue;
            }
            if (!$this->matchesNamespacePrefixes($className, $config->namespacePrefixes)) {
                continue;
            }
            if (str_contains($className, '@anonymous')) {
                continue;
            }

            $class = new \ReflectionClass($className);
            $classFile = $class->getFileName();
            if (!is_string($classFile) || '' === $classFile) {
                continue;
            }
            if ($this->isExcludedPath($classFile, $config)) {
                continue;
            }

            $type = $class->isInterface() ? 'interface' : ($class->isTrait() ? 'trait' : 'class');
            $classDoc = $class->getDocComment();
            $classTagged = $this->hasApiOrInternalTag(false === $classDoc ? null : $classDoc, $config->allowedTags);

            $classLine = $class->getStartLine() ?: 1;
            if (!$classTagged) {
                $tags = $tagIndexCache[$classFile] ??= $this->buildTagIndexForFile($classFile, $config->allowedTags);
                $classTagged = ($tags['class'][$classLine] ?? false) === true;
            }

            $publicMethodCount = 0;
            $coveredPublicMethodCount = 0;

            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $className) {
                    continue;
                }
                ++$publicMethodCount;

                $methodDoc = $method->getDocComment();
                $methodTagged = $this->hasApiOrInternalTag(false === $methodDoc ? null : $methodDoc, $config->allowedTags);

                if (!$methodTagged) {
                    $tags = $tagIndexCache[$classFile] ??= $this->buildTagIndexForFile($classFile, $config->allowedTags);
                    $methodLine = $method->getStartLine() ?: 0;
                    if ($methodLine > 0) {
                        $methodTagged = ($tags['function'][$methodLine] ?? false) === true;
                    }
                }

                if ($methodTagged || $classTagged) {
                    ++$coveredPublicMethodCount;
                    continue;
                }

                $missing[] = sprintf(
                    '%s:%d method %s::%s() missing @api/@internal',
                    $this->toProjectRelativePath($classFile, $config->projectRoot),
                    $method->getStartLine() ?: 1,
                    $className,
                    $method->getName(),
                );
            }

            $noPublicMethods = 0 === $publicMethodCount;
            $allMethodsCovered = $publicMethodCount > 0 && $coveredPublicMethodCount === $publicMethodCount;
            if (!$classTagged && !$allMethodsCovered && !$noPublicMethods) {
                $missing[] = sprintf(
                    '%s:%d %s %s missing @api/@internal',
                    $this->toProjectRelativePath($classFile, $config->projectRoot),
                    $classLine,
                    $type,
                    $className,
                );
            }
        }

        sort($missing, SORT_STRING);

        return $missing;
    }

    /** @return list<string> */
    private function getSourcePhpFiles(Config $config): array
    {
        $files = [];

        foreach ($config->paths as $path) {
            $dir = $this->resolveProjectPath($path, $config->projectRoot);
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo instanceof \SplFileInfo) {
                    continue;
                }
                if (!$fileInfo->isFile() || 'php' !== strtolower($fileInfo->getExtension())) {
                    continue;
                }
                $file = $fileInfo->getPathname();
                if ($this->isExcludedPath($file, $config)) {
                    continue;
                }
                $files[] = $file;
            }
        }

        $files = array_values(array_unique($files));
        sort($files, SORT_STRING);

        return $files;
    }

    /** @param list<string> $files @return list<class-string|interface-string|trait-string> */
    private function loadProjectSymbols(array $files): array
    {
        $beforeClasses = get_declared_classes();
        $beforeInterfaces = get_declared_interfaces();
        $beforeTraits = get_declared_traits();

        foreach ($files as $file) {
            require_once $file;
        }

        $classes = array_values(array_diff(get_declared_classes(), $beforeClasses));
        $interfaces = array_values(array_diff(get_declared_interfaces(), $beforeInterfaces));
        $traits = array_values(array_diff(get_declared_traits(), $beforeTraits));

        return array_values(array_unique(array_merge($classes, $interfaces, $traits)));
    }

    /** @return array{class: array<int, bool>, function: array<int, bool>} */
    private function buildTagIndexForFile(string $file, array $allowedTags): array
    {
        $code = file_get_contents($file);
        if (false === $code) {
            return ['class' => [], 'function' => []];
        }

        $tokens = token_get_all($code);
        $classTags = [];
        $functionTags = [];
        $lastComment = null;
        $pendingAttributeLine = null;
        $inAttribute = false;

        foreach ($tokens as $token) {
            if (is_string($token)) {
                if ($inAttribute) {
                    if (']' === $token) {
                        $inAttribute = false;
                    }
                    continue;
                }
                if ('' !== trim($token)) {
                    $lastComment = null;
                }
                continue;
            }

            [$id, $text, $line] = $token;

            if (in_array($id, [T_DOC_COMMENT, T_COMMENT], true)) {
                $lastComment = (is_string($lastComment) && '' !== $lastComment)
                    ? $lastComment."\n".$text
                    : $text;
                continue;
            }

            if (T_ATTRIBUTE === $id) {
                $pendingAttributeLine ??= $line;
                $inAttribute = true;
                continue;
            }

            if ($inAttribute) {
                continue;
            }

            if (in_array($id, [T_WHITESPACE, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_ABSTRACT, T_FINAL, T_STATIC, T_READONLY], true)) {
                continue;
            }

            if (in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                if ($this->hasApiOrInternalTag($lastComment, $allowedTags)) {
                    $classTags[$line] = true;
                    if (is_int($pendingAttributeLine) && $pendingAttributeLine > 0) {
                        $classTags[$pendingAttributeLine] = true;
                    }
                }
                $lastComment = null;
                $pendingAttributeLine = null;
                continue;
            }

            if (T_FUNCTION === $id) {
                if ($this->hasApiOrInternalTag($lastComment, $allowedTags)) {
                    $functionTags[$line] = true;
                    if (is_int($pendingAttributeLine) && $pendingAttributeLine > 0) {
                        $functionTags[$pendingAttributeLine] = true;
                    }
                }
                $lastComment = null;
                $pendingAttributeLine = null;
                continue;
            }

            $lastComment = null;
            $pendingAttributeLine = null;
        }

        return ['class' => $classTags, 'function' => $functionTags];
    }

    private function hasApiOrInternalTag(?string $doc, array $allowedTags): bool
    {
        if (!is_string($doc) || '' === trim($doc)) {
            return false;
        }

        foreach ($allowedTags as $tag) {
            if (str_contains($doc, $tag)) {
                return true;
            }
        }

        return false;
    }

    private function resolveProjectPath(string $path, string $root): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($root, '/').'/'.$path;
    }

    private function toProjectRelativePath(string $path, string $root): string
    {
        $pathReal = realpath($path) ?: $path;
        $rootReal = realpath($root) ?: $root;

        $prefix = rtrim($rootReal, '/').'/';
        if (str_starts_with($pathReal, $prefix)) {
            return substr($pathReal, strlen($prefix));
        }

        return $path;
    }

    private function matchesNamespacePrefixes(string $symbol, array $prefixes): bool
    {
        if ([] === $prefixes) {
            return true;
        }

        foreach ($prefixes as $prefix) {
            if (str_starts_with($symbol, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedPath(string $path, Config $config): bool
    {
        $target = realpath($path) ?: $path;
        foreach ($config->excludePaths as $pattern) {
            $needle = $this->resolveProjectPath($pattern, $config->projectRoot);
            $needleReal = realpath($needle) ?: $needle;
            if (str_contains($target, $needleReal)) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedSymbol(string $symbol, array $excludeSymbols): bool
    {
        foreach ($excludeSymbols as $excluded) {
            if ($symbol === $excluded) {
                return true;
            }
        }

        return false;
    }
}

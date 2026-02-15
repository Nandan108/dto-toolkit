<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Processing;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ErrorMessageRendererInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingExceptionInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBaseNoArgs;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Support\ContainerBridge;
use Nandan108\DtoToolkit\Support\DefaultErrorMessageRenderer;
use PHPUnit\Framework\TestCase;

final class ProcessingExceptionTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        ProcessingException::setMessageRenderer(null);
        DefaultErrorMessageRenderer::resetRuntimeConfig();
        ContainerBridge::setContainer(null);
        ContainerBridge::clearBindings();
    }

    public function testReturnsCustomErrorCode(): void
    {
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed('custom.reason', errorCode: 'custom-code');
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('custom-code', $exception->getErrorCode());
    }

    public function testThrowerNodeNameDefaultsToNull(): void
    {
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed('custom.reason');
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertNull($exception->getThrowerNodeName());
    }

    public function testThrowerNodeNameIsAutoEnrichedByProcessingNodeMetaInProdTraceMode(): void
    {
        BaseDto::clearAllCaches();
        ProcessingContext::setIncludeProcessingTraceInErrors(false);

        $dto = new class extends FullDto {
            #[CastTo\Boolean]
            public mixed $value = null;
        };

        try {
            $dto->fill(['value' => 'not-bool'])->processInbound();
            $this->fail('Expected exception not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('CastTo\Boolean', $e->getThrowerNodeName());
            $this->assertSame('value', $e->getPropertyPath());
        } finally {
            ProcessingContext::setIncludeProcessingTraceInErrors(null);
            BaseDto::clearAllCaches();
        }
    }

    public function testThrowerNodeNameCanBeCustomizedByNodeProducer(): void
    {
        BaseDto::clearAllCaches();
        ProcessingContext::setIncludeProcessingTraceInErrors(false);

        $dto = new class extends FullDto {
            #[CastTo(NodeNameOverrideCaster::class)]
            public mixed $value = null;
        };

        try {
            $dto->fill(['value' => 'x'])->processInbound();
            $this->fail('Expected exception not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('Custom\BooleanLike', $e->getThrowerNodeName());
            $this->assertSame('value', $e->getPropertyPath());
        } finally {
            ProcessingContext::setIncludeProcessingTraceInErrors(null);
            BaseDto::clearAllCaches();
        }
    }

    public function testGetMessageUsesDefaultRenderer(): void
    {
        // register a custom error message for "processing.custom.reason"
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'en',
            messages: [
                'processing.custom.reason' => 'Custom reason message.',
            ],
        );

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed('custom.reason');
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('Custom reason message.', $exception->getMessage());
    }

    public function testGetMessageUsesConfiguredRenderer(): void
    {
        ProcessingException::setMessageRenderer(new class implements ErrorMessageRendererInterface {
            #[\Override]
            public function render(ProcessingExceptionInterface $exception): string
            {
                return '[rendered] '.$exception->getMessageTemplate();
            }
        });

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed('custom.reason');
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('[rendered] processing.custom.reason', $exception->getMessage());
    }

    public function testGetMessageRendererResolvesFromContainerBridge(): void
    {
        ContainerBridge::register(ErrorMessageRendererInterface::class, new class implements ErrorMessageRendererInterface {
            #[\Override]
            public function render(ProcessingExceptionInterface $exception): string
            {
                return '[container] '.$exception->getMessageTemplate();
            }
        });

        ProcessingException::setMessageRenderer(null);

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed('custom.reason');
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('[container] processing.custom.reason', $exception->getMessage());
    }

    public function testDefaultRendererTranslatesExpectedAndTypeTokenLists(): void
    {
        ProcessingException::setMessageRenderer(null);

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = TransformException::expected(
                operand: 'abc',
                expected: ['type.numeric_string', 'type.int'],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('Expected a numeric string or an integer, got a string.', $exception->getMessage());
    }

    public function testDefaultRendererUsesLocaleWithFallbackAndRuntimeCatalogs(): void
    {
        ProcessingException::setMessageRenderer(null);

        DefaultErrorMessageRenderer::setLocale('fr_CH');
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr',
            messages: [
                'processing.transform.expected' => 'Attendu :expected, obtenu :type.',
            ],
            tokens: [
                'type.numeric_string' => 'une chaine numerique',
                'type.string'         => 'une chaine',
            ],
        );

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = TransformException::expected(
                operand: 'abc',
                expected: ['type.numeric_string'],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('Attendu une chaine numerique, obtenu une chaine.', $exception->getMessage());
    }

    public function testDefaultRendererUsesLocaleResolver(): void
    {
        ProcessingException::setMessageRenderer(null);

        ContainerBridge::register('app.locale', fn (): string => 'fr_FR');
        DefaultErrorMessageRenderer::setLocaleResolver(
            fn (): mixed => ContainerBridge::tryGet('app.locale'),
        );
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr',
            messages: [
                'processing.transform.expected' => 'Attendu :expected, obtenu :type.',
            ],
            tokens: [
                'type.numeric_string' => 'une chaine numerique',
                'type.string'         => 'une chaine',
            ],
        );

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = TransformException::expected(
                operand: 'abc',
                expected: ['type.numeric_string'],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('Attendu une chaine numerique, obtenu une chaine.', $exception->getMessage());
    }

    public function testDefaultRendererLocaleResolverCanBeClearedWithNull(): void
    {
        ProcessingException::setMessageRenderer(null);

        DefaultErrorMessageRenderer::setLocale('en');
        DefaultErrorMessageRenderer::setLocaleResolver(
            static fn (): string => 'fr_FR',
        );
        DefaultErrorMessageRenderer::setLocaleResolver(null);
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr',
            messages: [
                'processing.custom.reason' => 'FR message.',
            ],
        );
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'en',
            messages: [
                'processing.custom.reason' => 'EN message.',
            ],
        );

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed('custom.reason');
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('EN message.', $exception->getMessage());
    }

    public function testRegisterCatalogSupportsNonOverrideMerges(): void
    {
        ProcessingException::setMessageRenderer(null);

        DefaultErrorMessageRenderer::setLocale('fr');
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr',
            messages: [
                'processing.transform.expected' => 'Premier :expected / :type',
            ],
            tokens: [
                'type.string' => 'chaine-1',
            ],
            override: true,
        );
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr',
            messages: [
                'processing.transform.expected' => 'Second :expected / :type',
            ],
            tokens: [
                'type.string' => 'chaine-2',
            ],
            override: false,
        );

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = TransformException::expected(
                operand: 'abc',
                expected: ['type.numeric_string'],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('Premier a numeric string / chaine-1', $exception->getMessage());
    }

    public function testFallbackUsesFirstSameLanguageRegionalLocaleWhenNoGenericOrMappedExists(): void
    {
        ProcessingException::setMessageRenderer(null);

        DefaultErrorMessageRenderer::setLocale('fr_CH');
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr_FR',
            messages: [
                'processing.transform.expected' => 'FR-FR :expected / :type',
            ],
            tokens: [
                'type.numeric_string' => 'chaine numerique',
                'type.string'         => 'chaine',
            ],
        );

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = TransformException::expected(
                operand: 'abc',
                expected: ['type.numeric_string'],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('FR-FR chaine numerique / chaine', $exception->getMessage());
    }

    public function testLanguageDefaultMapTakesPrecedenceOverFirstSameLanguageRegionalLocale(): void
    {
        ProcessingException::setMessageRenderer(null);

        DefaultErrorMessageRenderer::setLocale('fr_CH');
        DefaultErrorMessageRenderer::setLanguageDefaultLocale('fr', 'fr_CA');
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr_FR',
            messages: [
                'processing.transform.expected' => 'FR-FR :expected / :type',
            ],
            tokens: [
                'type.numeric_string' => 'chaine numerique',
                'type.string'         => 'chaine',
            ],
        );
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr_CA',
            messages: [
                'processing.transform.expected' => 'FR-CA :expected / :type',
            ],
            tokens: [
                'type.numeric_string' => 'chaine num',
                'type.string'         => 'chaine ca',
            ],
        );

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = TransformException::expected(
                operand: 'abc',
                expected: ['type.numeric_string'],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('FR-CA chaine num / chaine ca', $exception->getMessage());
    }

    public function testSetLanguageDefaultLocalesMapIsApplied(): void
    {
        ProcessingException::setMessageRenderer(null);

        DefaultErrorMessageRenderer::setLocale('fr_CH');
        DefaultErrorMessageRenderer::setLanguageDefaultLocales([
            'fr' => 'fr_CA',
        ]);
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr_FR',
            messages: [
                'processing.transform.expected' => 'FR-FR :expected / :type',
            ],
            tokens: [
                'type.numeric_string' => 'chaine numerique',
                'type.string'         => 'chaine',
            ],
        );
        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr_CA',
            messages: [
                'processing.transform.expected' => 'FR-CA :expected / :type',
            ],
            tokens: [
                'type.numeric_string' => 'chaine num',
                'type.string'         => 'chaine ca',
            ],
        );

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = TransformException::expected(
                operand: 'abc',
                expected: ['type.numeric_string'],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('FR-CA chaine num / chaine ca', $exception->getMessage());
    }

    public function testClearCatalogCacheCanReloadBaseCatalogFilesWhenRequested(): void
    {
        ProcessingException::setMessageRenderer(null);

        $locale = 'zz_ZZ';
        $catalogDir = dirname(__DIR__, 3).'/resources/i18n/'.$locale;
        $messagesFile = $catalogDir.'/messages.php';
        $tokensFile = $catalogDir.'/tokens.php';

        if (!is_dir($catalogDir)) {
            mkdir($catalogDir, 0777, true);
        }
        file_put_contents($tokensFile, "<?php\n\ndeclare(strict_types=1);\n\nreturn [];\n");
        file_put_contents(
            $messagesFile,
            "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'processing.custom.reason' => 'v1',\n];\n",
        );

        try {
            DefaultErrorMessageRenderer::clearCatalogCache(true);
            DefaultErrorMessageRenderer::setLocale($locale);

            $dto = new class extends BaseDto {
            };
            $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
            ProcessingContext::pushFrame($frame);
            try {
                $first = ProcessingException::failed('custom.reason')->getMessage();
            } finally {
                ProcessingContext::popFrame();
            }
            $this->assertSame('v1', $first);

            file_put_contents(
                $messagesFile,
                "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'processing.custom.reason' => 'v2',\n];\n",
            );

            // Clear resolved cache only: base file cache should still keep v1.
            DefaultErrorMessageRenderer::clearCatalogCache();
            $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
            ProcessingContext::pushFrame($frame);
            try {
                $second = ProcessingException::failed('custom.reason')->getMessage();
            } finally {
                ProcessingContext::popFrame();
            }
            $this->assertSame('v1', $second);

            // Clear resolved + base catalog cache: should now pick v2 from disk.
            DefaultErrorMessageRenderer::clearCatalogCache(true);
            $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
            ProcessingContext::pushFrame($frame);
            try {
                $third = ProcessingException::failed('custom.reason')->getMessage();
            } finally {
                ProcessingContext::popFrame();
            }
            $this->assertSame('v2', $third);
        } finally {
            @unlink($messagesFile);
            @unlink($tokensFile);
            @rmdir($catalogDir);
            DefaultErrorMessageRenderer::clearCatalogCache(true);
        }
    }

    public function testJoinHumanProducesEmptySegmentForEmptyExpectedList(): void
    {
        ProcessingException::setMessageRenderer(null);
        DefaultErrorMessageRenderer::setLocale('en');

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed(
                template_suffix: 'guard.expected',
                parameters: [
                    'expected' => [],
                    'type'     => 'string',
                ],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('Expected , got a string.', $exception->getMessage());
    }

    public function testJoinHumanFormatsThreeExpectedItemsWithOxfordComma(): void
    {
        ProcessingException::setMessageRenderer(null);
        DefaultErrorMessageRenderer::setLocale('en');

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed(
                template_suffix: 'guard.expected',
                parameters: [
                    'expected' => ['type.string', 'type.int', 'type.float'],
                    'type'     => 'bool',
                ],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('Expected a string, an integer, or a float, got a boolean.', $exception->getMessage());
    }

    public function testUnrecognizedLocaleFallsBackToEnCatalog(): void
    {
        ProcessingException::setMessageRenderer(null);
        DefaultErrorMessageRenderer::setLocale('@!invalid-locale');

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed(
                template_suffix: 'guard.expected',
                parameters: [
                    'expected' => ['type.int'],
                    'type'     => 'string',
                ],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('Expected an integer, got a string.', $exception->getMessage());
    }

    public function testRegisterCatalogAcceptsHyphenLocaleIdAndResolvesWithNormalizedFallback(): void
    {
        ProcessingException::setMessageRenderer(null);
        DefaultErrorMessageRenderer::setLocale('fr_CH');

        DefaultErrorMessageRenderer::registerCatalog(
            locale: 'fr-FR',
            messages: [
                'processing.transform.expected' => 'FR-HYPHEN :expected / :type',
            ],
            tokens: [
                'type.numeric_string' => 'chaine numerique',
                'type.string'         => 'chaine',
            ],
        );

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = TransformException::expected(
                operand: 'abc',
                expected: ['type.numeric_string'],
            );
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('FR-HYPHEN chaine numerique / chaine', $exception->getMessage());
    }
}

final class NodeNameOverrideCaster extends CastBaseNoArgs
{
    /** @var ?truthy-string */
    protected static ?string $nodeName = 'Custom\BooleanLike';

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        throw TransformException::reason($value, 'custom.reason');
    }
}

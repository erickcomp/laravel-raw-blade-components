<?php

namespace ErickComp\RawBladeComponents;

use Illuminate\Support\Collection;
use Illuminate\View\Compilers\ComponentTagCompiler;

class RawComponentsManager
{
    protected Collection $rawComponents;
    protected Collection $rawComponentsStartingWith;

    public function __construct()
    {
        $this->rawComponents = new Collection();
        $this->rawComponentsStartingWith = new Collection();
    }

    // public function hasRegisteredRawComponents(): bool
    // {
    //     return $this->rawComponents->isNotEmpty() ||
    //         $this->rawComponentsStartingWith->isNotEmpty();
    // }

    public function rawComponent(
        string $tag,
        string $openingCode,
        string $closingCode,
        ?string $selfClosingCode = null,
        array $defaultAttributes = [],
    ): static {
        $this->rawComponents->put(
            $tag,
            new RawComponentCode(
                $tag,
                $openingCode,
                $closingCode,
                $selfClosingCode,
                $defaultAttributes,
            ),
        );

        return $this;
    }

    public function rawComponentStartingWith(
        string $tag,
        string $openingCode,
        string $closingCode,
        ?string $selfClosingCode = null,
        array $defaultAttributes = [],
    ): static {
        $this->rawComponentsStartingWith->put(
            $tag,
            new RawComponentCode(
                $tag,
                $openingCode,
                $closingCode,
                $selfClosingCode,
                $defaultAttributes,
            ),
        );

        $this->rawComponentsStartingWith = $this->rawComponentsStartingWith->sort(function (RawComponentCode $a, RawComponentCode $b) {
            if (strpos($b->tag, $a->tag) === 0) {
                return 1;  // $b is a longer version of $a, so $b should come first
            }
            if (strpos($a->tag, $b->tag) === 0) {
                return -1; // $a is a longer version of $b, so $a should come first
            }

            return strcmp($a->tag, $b->tag);
        });

        return $this;
    }

    public function compileRawBladeComponents(string $templateStr): string
    {
        $patterns = [
            $this->rawComponentSelfClosingTagRegex() => $this->rawComponentSelfClosingTagCompiler(...),
            $this->rawComponentOpeningTagRegex() => $this->rawComponentOpeningTagCompiler(...),
            $this->rawComponentClosingTagRegex() => $this->rawComponentClosingTagCompiler(...),
        ];

        return \preg_replace_callback_array(
            $patterns,
            $templateStr,
        );
    }

    protected function rawComponentOpeningTagCompiler(array $match): string
    {
        $componentTag = $match['componenttag'];

        if ($this->rawComponents->has($componentTag)) {
            $attributes = \array_merge(
                $this->rawComponents[$componentTag]->defaultAttributes,
                $this->getAttributesFromAttributeString($match['attributes']),
            );

            return '<?php ' . PHP_EOL
                . '$__previousRawComponentAttributes = $__rawComponentAttributes ?? new \\Illuminate\\View\\ComponentAttributeBag([]);' . PHP_EOL
                . '$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([' . $this->componentAttributesToString($attributes) . ']);' . PHP_EOL
                . '$__rawComponentTag = \'' . $componentTag . '\';' . PHP_EOL
                . '?>' . PHP_EOL
                . $this->rawComponents[$componentTag]->openingCode;
        }

        foreach ($this->rawComponentsStartingWith as $componentStartingWith => $rawComponent) {
            if (\str_starts_with($componentTag, $componentStartingWith)) {
                //$attributes = $this->getAttributesFromAttributeString($match['attributes']);
                $attributes = \array_merge(
                    $rawComponent->defaultAttributes,
                    $this->getAttributesFromAttributeString($match['attributes']),
                );

                return '<?php ' . PHP_EOL
                    . '$__previousRawComponentAttributes = $__rawComponentAttributes ?? new \\Illuminate\\View\\ComponentAttributeBag([]);' . PHP_EOL
                    . '$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([' . $this->componentAttributesToString($attributes) . ']);' . PHP_EOL
                    . '$__rawComponentTag = \'' . $componentTag . '\';' . PHP_EOL
                    . '?>' . PHP_EOL
                    . $rawComponent->openingCode;
            }
        }

        return $match[0];
    }

    protected function rawComponentClosingTagCompiler(array $match): string
    {
        $componentTag = $match['componenttag'];

        if ($this->rawComponents->has($componentTag)) {
            if ($this->rawComponents->has($componentTag)) {
                return $this->rawComponents[$componentTag]->closingCode . PHP_EOL
                    . '<?php' . PHP_EOL
                    . '$__rawComponentAttributes = $__previousRawComponentAttributes;' . PHP_EOL
                    . '$__previousRawComponentAttributes = null;' . PHP_EOL
                    . '?>' . PHP_EOL;
            }
        }

        foreach ($this->rawComponentsStartingWith as $componentStartingWith => $rawComponent) {
            if (\str_starts_with($componentTag, $componentStartingWith)) {
                return $this->rawComponentsStartingWith[$componentTag]->closingCode . PHP_EOL
                    . '<?php' . PHP_EOL
                    . '$__rawComponentAttributes = $__previousRawComponentAttributes;' . PHP_EOL
                    . '$__previousRawComponentAttributes = null;' . PHP_EOL
                    . '?>' . PHP_EOL;
            }
        }

        return $match[0];
    }

    protected function rawComponentSelfClosingTagCompiler(array $match): string
    {
        $componentTag = $match['componenttag'];

        if ($this->rawComponents->has($componentTag)) {
            if ($this->rawComponents->has($componentTag)) {
                if (isset($this->rawComponents[$componentTag]->selfClosingCode)) {
                    $attributes = \array_merge(
                        $this->rawComponents[$componentTag]->defaultAttributes,
                        $this->getAttributesFromAttributeString($match['attributes']),
                    );

                    return '<?php' . PHP_EOL
                        . '$__previousRawComponentAttributes = $__rawComponentAttributes ?? new \\Illuminate\\View\\ComponentAttributeBag([]);' . PHP_EOL
                        . '$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([' . $this->componentAttributesToString($attributes) . ']);' . PHP_EOL
                        . '$__rawComponentTag = \'' . $componentTag . '\';' . PHP_EOL
                        . '?>' . PHP_EOL
                        . $this->rawComponents[$componentTag]->selfClosingCode . PHP_EOL
                        . '<?php' . PHP_EOL
                        . '$__rawComponentAttributes = $__previousRawComponentAttributes;' . PHP_EOL
                        . '$__previousRawComponentAttributes = null;' . PHP_EOL
                        . '?>' . PHP_EOL;
                }

                return "<?php throw new \LogicException('The component [$componentTag] is not meant to be used with the self-closing tag syntax'); ?>";
            }
        }

        foreach ($this->rawComponentsStartingWith as $componentStartingWith => $rawComponent) {
            if (\str_starts_with($componentTag, $componentStartingWith)) {
                if (isset($rawComponent->selfClosingCode)) {
                    $attributes = $this->getAttributesFromAttributeString($match['attributes']);

                    return '<?php' . PHP_EOL
                        . '$__previousRawComponentAttributes = $__rawComponentAttributes ?? new \\Illuminate\\View\\ComponentAttributeBag([]);' . PHP_EOL
                        . '$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([' . $this->componentAttributesToString($attributes) . ']);' . PHP_EOL
                        . '$__rawComponentTag = \'' . $componentTag . '\';' . PHP_EOL
                        . '?>' . PHP_EOL
                        . $rawComponent->selfClosingCode . PHP_EOL
                        . '<?php' . PHP_EOL
                        . '$__rawComponentAttributes = $__previousRawComponentAttributes;' . PHP_EOL
                        . '$__previousRawComponentAttributes = null;' . PHP_EOL
                        . '?>' . PHP_EOL;
                }

                return "<?php throw new \LogicException('The component [$componentTag] is not meant to be used with the self-closing tag syntax'); ?>";
            }
        }

        return $match[0];
    }

    protected function rawComponentOpeningTagRegex(): string
    {
        return "/
            <
                \s*
                (?<componenttag>x[-\:]([\w\-\:\.]*))
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                (\:\\\$)(\w+)
                            )
                            |
                            (?:
                                [\w\-:.@%]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";
    }

    protected function rawComponentClosingTagRegex(): string
    {
        return "/<\/\s*(?<componenttag>x[-\:][\w\-\:\.]*)\s*>/";
    }

    protected function rawComponentSelfClosingTagRegex()
    {
        return "/
            <
                \s*
                (?<componenttag>x[-\:]([\w\-\:\.]*))
                \s*
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                (\:\\\$)(\w+)
                            )
                            |
                            (?:
                                [\w\-:.@%]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
            \/>
        /x";
    }

    protected function getAttributesFromAttributeString(string $attributesString)
    {
        $this->getLaravelComponentTagCompiler()->resetBoundAttributes();
        return $this->getLaravelComponentTagCompiler()->getAttributesFromAttributeString($attributesString);
    }

    protected function componentAttributesToString(array $attributes, bool $escapeBound = true): string
    {
        return $this->getLaravelComponentTagCompiler()->attributesToString($attributes, $escapeBound);
    }

    protected function getLaravelComponentTagCompiler()
    {
        static $compiler = null;

        if ($compiler === null) {
            $compiler = new class (app()->make(ComponentTagCompiler::class)) extends ComponentTagCompiler {

                public function __construct(ComponentTagCompiler $componentTagCompiler)
                {
                    parent::__construct($componentTagCompiler->aliases, $componentTagCompiler->namespaces, $componentTagCompiler->blade);
                }

                public function resetBoundAttributes()
                {
                    $this->boundAttributes = [];
                }

                public function getAttributesFromAttributeString(string $attributesString)
                {
                    return parent::getAttributesFromAttributeString($attributesString);
                }

                public function attributesToString(array $attributes, $escapeBound = true)
                {
                    return parent::attributesToString($attributes, $escapeBound);
                }
            };
        }

        return $compiler;
    }
}

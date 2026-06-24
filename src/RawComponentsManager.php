<?php

/**
 * ################ DO NOT TOUCH THIS FILE'S FORMATTING. ################
 * 
 * IF THE HEREDOCS GET OUT OF FORMATTING, THE TESTS WILL BREAK.
 */


namespace ErickComp\RawBladeComponents;

use Illuminate\Support\Collection;
use Illuminate\View\Compilers\ComponentTagCompiler;

class RawComponentsManager
{
    protected Collection $rawComponents;
    protected Collection $rawComponentsStartingWith;
    protected ?ComponentTagCompiler $laravelComponentTagCompiler = null;

    public function __construct()
    {
        $this->rawComponents = new Collection();
        $this->rawComponentsStartingWith = new Collection();
    }

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
        $safeComponentTag = \addslashes($componentTag);

        if ($this->rawComponents->has($componentTag)) {
            $attributes = \array_merge(
                $this->normalizeDefaultAttributes($this->rawComponents[$componentTag]->defaultAttributes),
                $this->getAttributesFromAttributeString($match['attributes']),
            );

            return <<<PHP_CODE
                    <?php
                    \$__rawComponentsStack ??= [];
                    \$__rawComponentsStack[] = [
                        '__rawComponentTagPrefix'  => \$__rawComponentTagPrefix ?? null,
                        '__rawComponentTag'        => \$__rawComponentTag ?? null,
                        '__rawComponentAttributes' => \$__rawComponentAttributes ?? null,
                    ];

                    \$__parentRawComponentTagPrefix = \$__rawComponentTagPrefix ?? null;
                    \$__parentRawComponentTag = \$__rawComponentTag ?? null;
                    \$__parentRawComponentAttributes = \$__rawComponentAttributes ?? null;

                    \$__rawComponentTagPrefix = '';
                    \$__rawComponentTag = '$safeComponentTag';
                    \$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([{$this->componentAttributesToString($attributes)}]);
                    ?>{$this->rawComponents[$componentTag]->openingCode}
                PHP_CODE;
        }

        foreach ($this->rawComponentsStartingWith as $componentStartingWith => $rawComponent) {
            if (\str_starts_with($componentTag, $componentStartingWith)) {
                $attributes = \array_merge(
                    $this->normalizeDefaultAttributes($rawComponent->defaultAttributes),
                    $this->getAttributesFromAttributeString($match['attributes']),
                );
                $safePrefix = \addslashes($componentStartingWith);

                return <<<PHP_CODE
                        <?php
                        \$__rawComponentsStack ??= [];
                        \$__rawComponentsStack[] = [
                            '__rawComponentTagPrefix'  => \$__rawComponentTagPrefix ?? null,
                            '__rawComponentTag'        => \$__rawComponentTag ?? null,
                            '__rawComponentAttributes' => \$__rawComponentAttributes ?? null,
                        ];

                        \$__parentRawComponentTagPrefix = \$__rawComponentTagPrefix ?? null;
                        \$__parentRawComponentTag = \$__rawComponentTag ?? null;
                        \$__parentRawComponentAttributes = \$__rawComponentAttributes ?? null;

                        \$__rawComponentTagPrefix = '$safePrefix';
                        \$__rawComponentTag = '$safeComponentTag';
                        \$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([{$this->componentAttributesToString($attributes)}]);
                        ?>
                        {$rawComponent->openingCode}
                    PHP_CODE;
            }
        }

        return $match[0];
    }

    protected function rawComponentClosingTagCompiler(array $match): string
    {
        $componentTag = $match['componenttag'];

        if ($this->rawComponents->has($componentTag)) {
                return <<<PHP_CODE
                    {$this->rawComponents[$componentTag]->closingCode}
                    <?php
                    \\extract(\\array_pop(\$__rawComponentsStack) ?? [], \EXTR_OVERWRITE);
                    ?>
                    PHP_CODE;
        }

        foreach ($this->rawComponentsStartingWith as $componentStartingWith => $rawComponent) {
            if (\str_starts_with($componentTag, $componentStartingWith)) {
                return <<<PHP_CODE
                    {$rawComponent->closingCode}
                    <?php \\extract(\\array_pop(\$__rawComponentsStack) ?? [], \EXTR_OVERWRITE); ?>
                    PHP_CODE;
            }
        }

        return $match[0];
    }

    protected function rawComponentSelfClosingTagCompiler(array $match): string
    {
        $componentTag = $match['componenttag'];
        $safeComponentTag = \addslashes($componentTag);

        if ($this->rawComponents->has($componentTag)) {
            if (isset($this->rawComponents[$componentTag]->selfClosingCode)) {
                $attributes = \array_merge(
                    $this->normalizeDefaultAttributes($this->rawComponents[$componentTag]->defaultAttributes),
                    $this->getAttributesFromAttributeString($match['attributes']),
                );

                return <<<PHP_CODE
                    <?php
                    \$__rawComponentsStack ??= [];
                    \$__rawComponentsStack[] = [
                        '__rawComponentTagPrefix'  => \$__rawComponentTagPrefix ?? null,
                        '__rawComponentTag'        => \$__rawComponentTag ?? null,
                        '__rawComponentAttributes' => \$__rawComponentAttributes ?? null,
                    ];
                    \$__parentRawComponentTagPrefix = \$__rawComponentTagPrefix ?? null;
                    \$__parentRawComponentTag = \$__rawComponentTag ?? null;
                    \$__parentRawComponentAttributes = \$__rawComponentAttributes ?? null;

                    \$__rawComponentTagPrefix = '';
                    \$__rawComponentTag = '$safeComponentTag';
                    \$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([{$this->componentAttributesToString($attributes)}]);
                    ?>
                    {$this->rawComponents[$componentTag]->selfClosingCode}
                    <?php \\extract(\\array_pop(\$__rawComponentsStack) ?? [], \EXTR_OVERWRITE); ?>
                    PHP_CODE;
            }

            return "<?php throw new \LogicException('The component [$componentTag] is not meant to be used with the self-closing tag syntax'); ?>";

        }

        foreach ($this->rawComponentsStartingWith as $componentStartingWith => $rawComponent) {
            if (\str_starts_with($componentTag, $componentStartingWith)) {
                if (isset($rawComponent->selfClosingCode)) {
                    $attributes = \array_merge(
                        $this->normalizeDefaultAttributes($rawComponent->defaultAttributes),
                        $this->getAttributesFromAttributeString($match['attributes']),
                    );
                    $safePrefix = \addslashes($componentStartingWith);

                    return <<<PHP_CODE
                            <?php
                            \$__rawComponentsStack ??= [];
                            \$__rawComponentsStack[] = [
                                '__rawComponentTagPrefix'  => \$__rawComponentTagPrefix ?? null,
                                '__rawComponentTag'        => \$__rawComponentTag ?? null,
                                '__rawComponentAttributes' => \$__rawComponentAttributes ?? null,
                            ];

                            \$__parentRawComponentTagPrefix = \$__rawComponentTagPrefix ?? null;
                            \$__parentRawComponentTag = \$__rawComponentTag ?? null;
                            \$__parentRawComponentAttributes = \$__rawComponentAttributes ?? null;

                            \$__rawComponentTagPrefix = '$safePrefix';
                            \$__rawComponentTag = '$safeComponentTag';
                            \$__rawComponentAttributes = new \\Illuminate\\View\\ComponentAttributeBag([{$this->componentAttributesToString($attributes)}]);
                            ?>{$rawComponent->selfClosingCode}<?php \\extract(\\array_pop(\$__rawComponentsStack) ?? [], \EXTR_OVERWRITE); ?>
                        PHP_CODE;
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

    protected function rawComponentSelfClosingTagRegex(): string
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

    protected function normalizeDefaultAttributes(array $defaultAttributes): array
    {
        $normalized = [];

        foreach ($defaultAttributes as $key => $value) {
            if (!\is_string($value) || $value === 'true' || \is_numeric($value)) {
                $normalized[$key] = $value;
                continue;
            }

            $firstChar = $value[0] ?? '';

            if ($firstChar === "'" || $firstChar === '"' || $firstChar === '$') {
                $normalized[$key] = $value;
                continue;
            }

            $normalized[$key] = "'" . \addslashes($value) . "'";
        }

        return $normalized;
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
        if ($this->laravelComponentTagCompiler === null) {
            $this->laravelComponentTagCompiler = new class (app()->make(ComponentTagCompiler::class)) extends ComponentTagCompiler {

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

        return $this->laravelComponentTagCompiler;
    }
}

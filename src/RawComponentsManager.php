<?php

namespace ErickComp\RawBladeComponents;

use Illuminate\Support\Collection;
use \Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\ComponentTagCompiler;

class RawComponentsManager extends ServiceProvider
{
    protected Collection $rawComponents;
    protected Collection $rawComponentsStartingWith;

    public function hasRegisteredRawComponents(): bool
    {
        return $this->rawComponents->isNotEmpty() ||
            $this->rawComponentsStartingWith->isNotEmpty();
    }

    public function rawComponent(
        string $tag,
        string $openingCode,
        string $closingCode,
        ?string $selfClosingCode = null,
    ): static {
        $this->rawComponents->put(
            $tag,
            new RawComponent(
                $tag,
                $openingCode,
                $closingCode,
                $selfClosingCode,
            ),
        );

        return $this;
    }

    public function rawComponentStartingWith(
        string $tag,
        string $openingCode,
        string $closingCode,
        ?string $selfClosingCode = null,
    ): static {
        $this->rawComponentsStartingWith->put(
            $tag,
            new RawComponent(
                $tag,
                $openingCode,
                $closingCode,
                $selfClosingCode,
            ),
        );

        return $this;
    }

    public function compileRawBladeComponents(string $templateStr)
    {
        // $templateStr = $this->compileSelfClosingTags($templateStr);
        // $templateStr = $this->compileOpeningTags($templateStr);
        // $templateStr = $this->compileClosingTags($templateStr);

        // return $templateStr;

        $patterns = [
            $this->rawComponentSelfClosingTagRegex(),
            $this->rawComponentOpeningTagRegex(),
            $this->rawComponentClosingTagRegex(),
        ];

        $callbacks = [
            $this->rawComponentSelfClosingTagCompiler(),
            $this->rawComponentOpeningTagCompiler(),
            $this->rawComponentClosingTagCompiler(),
        ];

        return \preg_replace_callback_array(
            $patterns,
            $templateStr,
            $callbacks,
        );
    }

    protected function rawComponentOpeningTagCompiler(string $templateStr): string {}

    protected function rawComponentClosingTagCompiler(string $templateStr): string {}

    protected function rawComponentSelfClosingTagCompiler(string $templateStr): string {}

    protected function rawComponentOpeningTagRegex(): string
    {
        return "/
            <
                \s*
                (?<component-tag>x[-\:]([\w\-\:\.]*))
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
        return "/<\/\s*(?<component-tag>x[-\:][\w\-\:\.]*)\s*>/";
    }

    protected function rawComponentSelfClosingTagRegex()
    {
        return "/
            <
                \s*
                (?<component-tag>x[-\:]([\w\-\:\.]*))
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

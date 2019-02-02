<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class Emphasis implements Inline
{
    use WidthTrait, DefaultBeginPosition;

    /** @var string */
    private $text;

    /** @var 'em'|'strong' */
    private $type;

    /** @var array{*: string, _: string} */
    private static $STRONG_REGEX = [
        '*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*+[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:\\\\_|[^_]|_[^_]*+_)+?)__(?!_)/us',
    ];

    /** @var array{*: string, _: string} */
    private static $EM_REGEX = [
        '*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
    ];

    /**
     * @param string $text
     * @param 'em'|'strong' $type
     * @param int $width
     */
    public function __construct($text, $type, $width)
    {
        $this->text = $text;
        $this->type = $type;
        $this->width = $width;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        if (\strlen($Excerpt->text()) < 3) {
            return null;
        }

        $marker = \substr($Excerpt->text(), 0, 1);

        if ($marker !== '*' && $marker !== '_') {
            return null;
        }

        if (
            \substr($Excerpt->text(), 1, 1) === $marker
            && \preg_match(self::$STRONG_REGEX[$marker], $Excerpt->text(), $matches)
        ) {
            $emphasis = 'strong';
        } elseif (\preg_match(self::$EM_REGEX[$marker], $Excerpt->text(), $matches)) {
            $emphasis = 'em';
        } else {
            return null;
        }

        return new self($matches[1], $emphasis, \strlen($matches[0]));
    }

    /**
     * @return Handler<Element>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element */
            function (State $State) {
                return new Element(
                    $this->type,
                    [],
                    $State->applyTo(Parsedown::line($this->text, $State))
                );
            }
        );
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->text);
    }
}

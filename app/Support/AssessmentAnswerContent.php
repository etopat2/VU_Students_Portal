<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class AssessmentAnswerContent
{
    private const ALLOWED_TAGS = [
        'blockquote',
        'br',
        'em',
        'h2',
        'h3',
        'li',
        'ol',
        'p',
        'strong',
        'u',
        'ul',
    ];

    public static function sanitize(string $content): string
    {
        if (trim($content) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorSetting = libxml_use_internal_errors(true);

        try {
            $document->loadHTML(
                '<?xml encoding="UTF-8"><div>'.$content.'</div>',
                LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorSetting);
        }

        $root = $document->getElementsByTagName('div')->item(0);

        return $root === null ? '' : self::renderChildren($root);
    }

    public static function hasText(string $content): bool
    {
        $text = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text) !== '';
    }

    private static function renderChildren(DOMNode $node): string
    {
        $content = '';

        foreach ($node->childNodes as $child) {
            $content .= self::renderNode($child);
        }

        return $content;
    }

    private static function renderNode(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if (! $node instanceof DOMElement) {
            return self::renderChildren($node);
        }

        $tag = strtolower($node->tagName);
        $children = self::renderChildren($node);

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            return $children;
        }

        return $tag === 'br' ? '<br>' : '<'.$tag.'>'.$children.'</'.$tag.'>';
    }
}

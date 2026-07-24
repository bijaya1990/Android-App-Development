<?php
/**
 * Strips the rich-text editor's HTML output down to a small safe
 * allowlist before it is stored or rendered anywhere.
 */
declare(strict_types=1);

final class Sanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'blockquote', 'a'];

    public static function articleHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8"?><div>' . $html . '</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $container = $dom->getElementsByTagName('div')->item(0);
        if ($container === null) {
            return '';
        }

        self::cleanNode($dom, $container);

        $out = '';
        foreach (iterator_to_array($container->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private static function cleanNode(DOMDocument $dom, DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if (!($child instanceof DOMElement)) {
                continue; // text nodes are kept as-is
            }

            $tag = strtolower($child->tagName);

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                if ($tag === 'a' && $attr->name === 'href' && preg_match('/^https?:\/\//i', trim($attr->value))) {
                    continue;
                }
                $child->removeAttribute($attr->name);
            }

            if ($tag === 'a' && $child->hasAttribute('href')) {
                $child->setAttribute('rel', 'noopener noreferrer');
                $child->setAttribute('target', '_blank');
            }

            self::cleanNode($dom, $child);
        }
    }
}

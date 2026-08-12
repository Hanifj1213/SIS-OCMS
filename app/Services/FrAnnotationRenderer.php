<?php

namespace App\Services;

/**
 * Mengubah anotasi vektor FR menjadi SVG transparan. SVG dipasang sebagai
 * image layer agar DomPDF dapat mencetak panah dan teks tepat pada koordinat
 * yang sama dengan editor web (viewBox 0..100 x 0..100).
 */
class FrAnnotationRenderer
{
    // Koordinat disimpan sebagai persentase kanvas di editor web.
    private const WIDTH = 100.0;

    private const HEIGHT = 100.0;

    /**
     * @param  list<array<string, mixed>>  $annotations
     */
    public function dataUri(array $annotations): ?string
    {
        $svg = $this->svg($annotations);

        return $svg === null ? null : 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * @param  list<array<string, mixed>>  $annotations
     */
    public function svg(array $annotations): ?string
    {
        $elements = [];

        foreach ($annotations as $annotation) {
            $type = str_replace('_', '-', (string) ($annotation['type'] ?? ''));
            $color = $this->color((string) ($annotation['color'] ?? ''));

            $type = str_replace('_', '-', $type);

            if (in_array($type, ['line', 'arrow', 'double-arrow', 'connector'], true)) {
                $x1 = $this->x($annotation['x1'] ?? 0);
                $y1 = $this->y($annotation['y1'] ?? 0);
                $x2 = $this->x($annotation['x2'] ?? 0);
                $y2 = $this->y($annotation['y2'] ?? 0);
                $stroke = $this->between((float) ($annotation['stroke'] ?? 2), 1, 8);
                $strokeWidth = $stroke * 0.35;

                $elements[] = sprintf(
                    '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="%s" stroke-linecap="round"/>',
                    $this->number($x1),
                    $this->number($y1),
                    $this->number($x2),
                    $this->number($y2),
                    $color,
                    $this->number($strokeWidth)
                );

                $lineLength = hypot($x2 - $x1, $y2 - $y1);
                $arrowSize = min($lineLength * 0.30, 5.0 + $stroke * 0.35);
                if ($type === 'arrow' || $type === 'double-arrow') {
                    $elements[] = $this->arrowHead($x2, $y2, $x1, $y1, $arrowSize, $color);
                }
                if ($type === 'double-arrow') {
                    $elements[] = $this->arrowHead($x1, $y1, $x2, $y2, $arrowSize, $color);
                }
                if ($type === 'connector') {
                    $radius = max(4.0, $stroke * 2.4);
                    $elements[] = sprintf(
                        '<circle cx="%s" cy="%s" r="%s" fill="%s"/><circle cx="%s" cy="%s" r="%s" fill="%s"/>',
                        $this->number($x1),
                        $this->number($y1),
                        $this->number($radius),
                        $color,
                        $this->number($x2),
                        $this->number($y2),
                        $this->number($radius),
                        $color
                    );
                }

                continue;
            }

            if ($type !== 'text') {
                continue;
            }

            $text = trim((string) ($annotation['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $x = $this->x($annotation['x'] ?? 0);
            $y = $this->y($annotation['y'] ?? 0);
            $fontSize = $this->between(
                (float) ($annotation['font_size'] ?? $annotation['size'] ?? 5),
                2,
                15
            );
            $lines = preg_split('/\R/u', $text) ?: [$text];
            $spans = [];

            foreach (array_slice($lines, 0, 5) as $index => $line) {
                $escaped = htmlspecialchars($line, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $spans[] = sprintf(
                    '<tspan x="%s" dy="%s">%s</tspan>',
                    $this->number($x),
                    $index === 0 ? '0' : $this->number($fontSize * 1.08),
                    $escaped
                );
            }

            $elements[] = sprintf(
                '<text x="%s" y="%s" fill="%s" font-family="Arial, Helvetica, sans-serif" font-size="%s" font-weight="600" dominant-baseline="hanging">%s</text>',
                $this->number($x),
                $this->number($y),
                $color,
                $this->number($fontSize),
                implode('', $spans)
            );
        }

        if ($elements === []) {
            return null;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" preserveAspectRatio="none">%s</svg>',
            (int) self::WIDTH,
            (int) self::HEIGHT,
            implode('', $elements)
        );
    }

    private function arrowHead(
        float $tipX,
        float $tipY,
        float $tailX,
        float $tailY,
        float $size,
        string $color
    ): string {
        $dx = $tipX - $tailX;
        $dy = $tipY - $tailY;
        $length = hypot($dx, $dy);

        if ($length < 0.01) {
            return '';
        }

        $ux = $dx / $length;
        $uy = $dy / $length;
        $baseX = $tipX - ($ux * $size);
        $baseY = $tipY - ($uy * $size);
        $half = $size * 0.62;
        $perpX = -$uy * $half;
        $perpY = $ux * $half;

        return sprintf(
            '<polygon points="%s,%s %s,%s %s,%s" fill="%s"/>',
            $this->number($tipX),
            $this->number($tipY),
            $this->number($baseX + $perpX),
            $this->number($baseY + $perpY),
            $this->number($baseX - $perpX),
            $this->number($baseY - $perpY),
            $color
        );
    }

    private function x(mixed $percent): float
    {
        return self::WIDTH * $this->between((float) $percent, -20, 120) / 100;
    }

    private function y(mixed $percent): float
    {
        return self::HEIGHT * $this->between((float) $percent, -20, 120) / 100;
    }

    private function between(float $value, float $min, float $max): float
    {
        return min($max, max($min, $value));
    }

    private function color(string $color): string
    {
        return preg_match('/^#[0-9a-f]{6}$/i', $color) ? strtolower($color) : '#ef4444';
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}

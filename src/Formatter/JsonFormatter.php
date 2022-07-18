<?php

namespace KJLJon\StreamingIterable\Formatter;

final class JsonFormatter implements StreamFormatterInterface
{
    private bool $first = true;

    public function transform(array|object $row, bool $eof): string
    {
        $prefix = $this->first ? '[' : ',';
        $suffix = $eof ? ']' : '';

        if ($this->first) {
            $this->first = false;
        }

        return $prefix . json_encode($row) . $suffix;
    }
}

<?php

namespace KJLJon\StreamingIterable\Formatter;

interface StreamFormatterInterface
{
    public function transform(array|object $row, bool $eof): string;
}

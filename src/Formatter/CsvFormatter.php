<?php

namespace KJLJon\StreamingIterable\Formatter;

final class CsvFormatter implements StreamFormatterInterface
{
    private mixed $fp;

    public function __construct(
        private string $separator = ',',
        private string $enclosure = '"',
        private string $escape = '\\',
        private string $eol = PHP_EOL
    ) {
        $this->fp = fopen('php://temp', 'w+b');
    }

    public function transform(array|object $row, bool $eof): string
    {
        $fp = $this->fp;

        ftruncate($fp, 0);
        fputcsv($fp, (array) $row, $this->separator, $this->enclosure, $this->escape, $this->eol);
        rewind($fp);

        return stream_get_contents($fp);
    }

    public function __destruct()
    {
        fclose($this->fp);
    }


}

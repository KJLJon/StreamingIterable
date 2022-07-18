<?php

namespace KJLJon\Tests;

use KJLJon\StreamingIterable\Formatter\CsvFormatter;
use KJLJon\StreamingIterable\Formatter\JsonFormatter;
use KJLJon\StreamingIterable\IterableToReaderStream;
use KJLJon\StreamingIterable\StreamWrapper;
use PHPUnit\Framework\TestCase;

class IterableToReaderStreamTest extends TestCase
{
    /** @test */
    public function can_stream_csv(): void
    {
        $formatter = new CsvFormatter();
        $content = $this->getContent($formatter);

        $this->assertEquals(
            'Jim,Thompson,April' . PHP_EOL .
            'Tim,Johnson,December' . PHP_EOL,
            $content
        );
    }

    /** @test */
    public function can_stream_json(): void
    {
        $formatter = new JsonFormatter();
        $content = $this->getContent($formatter);

        $this->assertEquals(
            '[{"first":"Jim","last":"Thompson","month":"April"},{"first":"Tim","last":"Johnson","month":"December"}]',
            $content
        );
    }

    protected function getContent($formatter): string
    {
        $iterable = [
            ['first' => 'Jim', 'last' => 'Thompson', 'month' => 'April'],
            ['first' => 'Tim', 'last' => 'Johnson', 'month' => 'December']
        ];
        $stream = new IterableToReaderStream($iterable, $formatter);
        $fp = StreamWrapper::getResource($stream);

        return stream_get_contents($fp);
    }
}

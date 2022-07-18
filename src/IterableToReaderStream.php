<?php

namespace KJLJon\StreamingIterable;

use KJLJon\StreamingIterable\Formatter\StreamFormatterInterface;
use Generator;
use Iterator;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class IterableToReaderStream implements StreamInterface
{
    const DEFAULT_LENGTH = 8192;

    private int $pos = 0;
    private bool $eof = false;
    private string $buffer = '';
    private int $offset = 0;

    public function __construct(
        private iterable $iterable,
        private StreamFormatterInterface $formatter
    ) {}

    public function __toString()
    {
        return $this->getContents();
    }

    public function close(): void
    {
        unset($this->iterable);
    }

    public function detach(): void
    {
        unset($this->iterable);
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return $this->pos;
    }

    public function eof(): bool
    {
        return $this->eof;
    }

    public function isSeekable(): bool
    {
        return is_array($this->iterable) ||
            (
                $this->iterable instanceof Iterator
                && ! $this->iterable instanceof Generator
            );
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('the iterator provided isn\'t seekable');
        }

        $this->pos = 0;
        $this->offset = 0;
        $this->buffer = '';
        $this->eof = false;

        if (is_array($this->iterable)) {
            reset($this->iterable);
        } elseif ($this->iterable instanceof Iterator) {
            $this->iterable->rewind();
        } else {
            throw new RuntimeException('Unsupported stream type');
        }

        do {
            $length = min(self::DEFAULT_LENGTH, $offset - $this->tell());
        } while ($length && $this->read($length) !== '');
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new RuntimeException('this is a read only stream');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        if ($this->eof) {
            return '';
        }

        $iterable = $this->iterable;
        if (is_array($iterable)) {
            $iterable = array_values($iterable);
        }

        $buffer = $this->buffer($iterable, $length);

        return $this->getContent($buffer, $length);
    }

    private function getContent(string $buffer, int $length): string
    {
        $content = substr($buffer, 0, $length);
        $this->buffer = substr($buffer, $length);
        $this->pos += strlen($content);

        return $content;
    }

    private function buffer(iterable $iterable, int $length): string
    {
        $buffer = $this->buffer;

        while (strlen($buffer) < $length && $this->eof() === false) {
            if (is_array($iterable)) {
                $data = $iterable[$this->offset];
                $this->offset++;
                $this->eof = !isset($iterable[$this->offset]);
            } elseif ($iterable instanceof \Iterator) {
                $data = $iterable->current();
                $iterable->next();
                $this->eof = !$iterable->valid();
            } else {
                throw new RuntimeException('Unsupported stream type');
            }

            $buffer .= $this->formatter->transform($data, $this->eof);
        }

        return $buffer;
    }

    public function getContents(): string
    {
        if ($this->tell() !== 0) {
            if (!$this->isSeekable()) {
                throw new RuntimeException('Unable to get all the content since the iterator isn\'t seekable');
            }

            $this->rewind();
        }

        $content = '';
        while ($this->eof() === false && $data = $this->read(self::DEFAULT_LENGTH)) {
            $content .= $data;
        }

        return $content;
    }

    public function getMetadata($key = null): mixed
    {
        $keys = [
            'timed_out' => false,
            'blocked' => false,
            'eof' => $this->eof(),
            'unread_bytes' => strlen($this->buffer),
            'stream_type' => StreamWrapper::PROTOCOL,
            'wrapper_type' => StreamWrapper::PROTOCOL,
            'mode' => 'r',
            'seekable' => $this->isSeekable(),
            'uri' => StreamWrapper::URI,
        ];

        if ($key === null) {
            return $keys;
        }

        return $keys[$key] ?? null;
    }
}


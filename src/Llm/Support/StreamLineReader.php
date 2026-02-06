<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Llm\Support;

use Generator;
use Psr\Http\Message\StreamInterface;

class StreamLineReader
{
    /**
     * Read lines from a PSR-7 stream, yielding each non-empty line.
     *
     * @return Generator<int, string>
     */
    public static function readLines(StreamInterface $stream): Generator
    {
        $buffer = '';

        while (! $stream->eof()) {
            $buffer .= $stream->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                $line = trim($line);
                if ($line !== '') {
                    yield $line;
                }
            }
        }

        if (trim($buffer) !== '') {
            yield trim($buffer);
        }
    }
}

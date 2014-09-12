<?php

namespace Aztech\Sniffs\Formatting;

use Aztech\Sniffs\TokenIterator;

/**
 * Sniff to detect that there are no consecutive blank lines in code.
 *
 * @author thibaud
 */
class NoConsecutiveBlankLinesSniff implements \PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(T_FUNCTION);
    }

    public function process(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $function = $tokens[$stackPtr];

        if (! isset($function['scope_opener'])) {
            // No scope means it's an abstract/interface function declaration
            return;
        }

        $bodyStartPtr = $function['scope_opener'];
        $bodyEndPtr = $function['scope_closer'];

        $it = new TokenIterator($tokens, $bodyStartPtr - 1, $bodyEndPtr + 1);
        $buffer = '';

        foreach ($it as $ptr => $token) {
            $buffer .= str_replace(' ', '', $token['content']);

            if (strpos($buffer, PHP_EOL . PHP_EOL . PHP_EOL) !== false) {
                $error = 'There must be no consecutive blank lines';
                $phpcsFile->addError($error, $ptr, 'ConsecutiveBlankLines');

                while (strpos($buffer, PHP_EOL . PHP_EOL . PHP_EOL) !== false) {
                    $buffer = str_replace(PHP_EOL . PHP_EOL . PHP_EOL, PHP_EOL . PHP_EOL, $buffer);
                }
            }
        }
    }
}

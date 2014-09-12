<?php

namespace Aztech\Sniffs\Formatting;

use Aztech\Sniffs\TokenIterator;
/**
 * Sniff to detect that return statements are correctly wrapped with new lines when necessary.
 * @author thibaud
 *
 */
class ReturnFormattingSniff implements \PHP_CodeSniffer_Sniff
{

    private $allowedTypes = [
        T_WHITESPACE, T_COMMENT, T_DOC_COMMENT
    ];

    public function register()
    {
        return array(T_RETURN);
    }

    public function process(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $this->processTokensBeforeReturn($phpcsFile, $stackPtr, $tokens);
        $this->processTokensAfterReturn($phpcsFile, $stackPtr, $tokens);
    }

    private function processTokensBeforeReturn(\PHP_CodeSniffer_File $phpcsFile, $stackPtr, $tokens)
    {
        $prevPtr = $phpcsFile->findPrevious([ T_OPEN_CURLY_BRACKET, T_CLOSE_CURLY_BRACKET, T_SEMICOLON], $stackPtr);

        $startToken = $tokens[$prevPtr];
        $it = new TokenIterator($tokens, $prevPtr, $stackPtr);
        $newLineCount = 0;

        foreach ($it as $token) {
            if ($token['code'] == T_WHITESPACE) {
                $newLineCount += substr_count($token['content'], PHP_EOL);
            }
        }

        if ($startToken['code'] == T_OPEN_CURLY_BRACKET && $newLineCount > 1) {
            $error = 'Additional blank lines found before return statement';
            $phpcsFile->addError($error, $stackPtr, 'ExtraBlankLines');
        }
        elseif ($startToken['code'] == T_SEMICOLON || $startToken['code'] == T_CLOSE_CURLY_BRACKET) {
            if ($newLineCount > 2) {
                $error = 'Additional blank lines found before return statement';
                $phpcsFile->addError($error, $stackPtr, 'ExtraBlankLines');
            }
            elseif ($newLineCount < 2) {
                $error = 'There must be exactly one blank line before this return statement.';
                $phpcsFile->addError($error, $stackPtr, 'MissingBlankLines');
            }
        }

        return;
    }

    private function processTokensAfterReturn(\PHP_CodeSniffer_File $phpcsFile, $stackPtr, $tokens)
    {
        $nextPtr = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1);
        $closePtr = $phpcsFile->findNext(T_CLOSE_CURLY_BRACKET, $nextPtr + 1);
        $it = new TokenIterator($tokens, $nextPtr + 1, $closePtr);

        $newLineCount = 0;

        foreach ($it as $token) {
            if (! in_array($token['code'], $this->allowedTypes)) {
                $error = 'Return statement should be the last statement in scope';
                $phpcsFile->addError($error, $it, 'NotLastInScope');

                break;
            }

            if ($token['code'] == T_WHITESPACE) {
                $newLineCount += substr_count($token['content'], PHP_EOL);
            }
        }

        if ($newLineCount > 1) {
            $error = 'Additional blank lines found after return statement';
            $phpcsFile->addError($error, $stackPtr, 'ExtraBlankLines');
        }
    }
}

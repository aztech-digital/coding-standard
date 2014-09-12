<?php

namespace Aztech\Sniffs\Formatting;

use Aztech\Sniffs\TokenIterator;

/**
 * Sniff to detect that there are no consecutive blank lines in code.
 *
 * @author thibaud
 */
class ScopeFormattingSniff implements \PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return [
            T_FUNCTION
        ];
    }

    public function process(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $scope = $phpcsFile->getDeclarationName($stackPtr);
        $class = $phpcsFile->getDeclarationName($phpcsFile->findPrevious([ T_CLASS, T_INTERFACE], $stackPtr));

        $tokens = $phpcsFile->getTokens();
        $function = $tokens[$stackPtr];

        $scopeTypes = [
            T_IF,
            T_FOR,
            T_FOREACH,
            T_SWITCH
        ];

        $functionStartPtr = $function['scope_opener'];
        $functionEndPtr = $function['scope_closer'];
        $ptr = $functionStartPtr;

        while ($ptr = $phpcsFile->findNext($scopeTypes, $ptr + 1, $functionEndPtr)) {
            $this->getBlankLineCountBefore($phpcsFile, $tokens, $functionStartPtr, $ptr);
            $this->getBlankLineCountAfter($phpcsFile, $tokens, $tokens[$ptr]['scope_closer'], $functionEndPtr);
        }
    }

    private function getBlankLineCountBefore(\PHP_CodeSniffer_File $phpcsFile, $tokens, $startPtr, $endPtr)
    {
        $it = new TokenIterator($tokens, $startPtr, $endPtr);
        $brokenBy = null;
        $newLines = 0;

        foreach ($it->reverse() as $ptr => $token) {
            if ($token['code'] == T_SEMICOLON || $token['code'] == T_OPEN_CURLY_BRACKET || $token['code'] == T_CLOSE_CURLY_BRACKET) {
                $brokenBy = $token;

                break;
            }

            if ($token['code'] == T_WHITESPACE) {
                $content = str_replace(' ', '', $token['content']);
                $newLines += substr_count($content, PHP_EOL);
            }
        }

        if ($newLines != 2 && $brokenBy['code'] != T_OPEN_CURLY_BRACKET) {
            $error = 'Control blocks should be preceded by exactly one blank line (' . max(0, $newLines - 1) . ' found)';
            $phpcsFile->addError($error, $endPtr, 'NotExactlyOneBlankLine');
        }

        elseif ($newLines > 1 && $brokenBy['code'] == T_OPEN_CURLY_BRACKET) {
            $error = 'Immediately nested control blocks should not be preceded by blank lines (' . max(0, $newLines - 1) . ' found)';
            $phpcsFile->addError($error, $ptr + 2, 'ExtraBlankLines');
        }
    }

    private function getBlankLineCountAfter(\PHP_CodeSniffer_File $phpcsFile, $tokens, $startPtr, $endPtr)
    {
        $it = new TokenIterator($tokens, $startPtr, $endPtr);
        $brokenBy = null;
        $newLines = 0;

        foreach ($it as $ptr => $token) {
            if ($token['code'] == T_SEMICOLON || $token['code'] == T_OPEN_CURLY_BRACKET || $token['code'] == T_CLOSE_CURLY_BRACKET) {
                $brokenBy = $token;

                break;
            }

            if ($token['code'] == T_WHITESPACE) {
                $content = str_replace(' ', '', $token['content']);
                $newLines += substr_count($content, PHP_EOL);
            }

        }

        if ($newLines != 2 && $brokenBy['code'] != T_CLOSE_CURLY_BRACKET) {
            $error = 'Control blocks should be followed by exactly one blank line (' . max(0, $newLines - 1) . ' found)';
            $phpcsFile->addError($error, $endPtr, 'NotExactlyOneBlankLine');
        }
    }
}

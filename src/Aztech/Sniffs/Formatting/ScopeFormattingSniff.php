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
        $class = $phpcsFile->getDeclarationName(
            $phpcsFile->findPrevious([
                T_CLASS,
                T_INTERFACE
            ], $stackPtr));

        $tokens = $phpcsFile->getTokens();
        $function = $tokens[$stackPtr];

        if (! isset($function['scope_opener'])) {
            // No scope means it's an abstract/interface function declaration
            return;
        }

        $scopeTypes = [
            T_IF,
            T_ELSE,
            T_ELSEIF,
            T_FOR,
            T_FOREACH,
            T_SWITCH,
            T_WHILE
        ];

        $functionStartPtr = $function['scope_opener'];
        $functionEndPtr = $function['scope_closer'];
        $ptr = $functionStartPtr;

        // var_dump($class, $scope);

        while ($ptr = $phpcsFile->findNext($scopeTypes, $ptr + 1, $functionEndPtr)) {
            $token = $tokens[$ptr];

            if ($token['code'] != T_IF && $token['code'] != T_ELSE && $token['code'] != T_ELSEIF) {
                $this->getBlankLineCountBefore($phpcsFile, $tokens, $functionStartPtr, $ptr - 1);
                $this->getBlankLineCountAfter($phpcsFile, $tokens, $token['scope_closer'] + 1, $functionEndPtr);
            }
            elseif ($token['code'] == T_IF) {
                $this->getBlankLineCountBefore($phpcsFile, $tokens, $functionStartPtr, $ptr);
                $this->getBlankLineCountAfter($phpcsFile, $tokens, $token['scope_closer'] + 1, $functionEndPtr,
                    [
                        T_ELSE,
                        T_ELSEIF
                    ]);
            }
            elseif ($token['code'] == T_ELSEIF) {
                $this->getBlankLineCountBefore($phpcsFile, $tokens, $functionStartPtr, $ptr, [
                    T_CLOSE_CURLY_BRACKET
                ]);
                $this->getBlankLineCountAfter($phpcsFile, $tokens, $token['scope_closer'] + 1, $functionEndPtr,
                    [
                        T_ELSE,
                        T_ELSEIF
                    ]);
            }
            elseif ($token['code'] == T_ELSE) {
                $this->getBlankLineCountBefore($phpcsFile, $tokens, $functionStartPtr, $ptr, [
                    T_CLOSE_CURLY_BRACKET
                ]);
                $this->getBlankLineCountAfter($phpcsFile, $tokens, $token['scope_closer'] + 1, $functionEndPtr);
            }
        }
    }

    private function getBlankLineCountBefore(\PHP_CodeSniffer_File $phpcsFile, $tokens, $startPtr, $endPtr,
        array $extraTokens = array())
    {
        $it = new TokenIterator($tokens, $startPtr, $endPtr);
        $brokenBy = null;
        $newLines = 0;

        foreach ($it->reverse() as $ptr => $token) {
            if ($token['code'] == T_WHITESPACE) {
                $content = str_replace(' ', '', $token['content']);
                $newLines += substr_count($content, PHP_EOL);
            }
            else {
                $brokenBy = $token;

                break;
            }
        }

        $noExtraBlanksTokens = array_merge([
            T_OPEN_CURLY_BRACKET
        ], $extraTokens);

        if ($brokenBy['code'] == T_COMMENT) {
            $newLines ++;
        }

        if ($newLines != 2 && $brokenBy && ! in_array($brokenBy['code'], $noExtraBlanksTokens, true)) {
            $error = 'Control blocks should be preceded by exactly one blank line (' . max(0, $newLines - 1) .
                 ' found - ' . $brokenBy['type'] . ')';
            $phpcsFile->addError($error, $endPtr, 'NotExactlyOneBlankLine');
        }
        elseif ($newLines > 1 && (! $brokenBy || in_array($brokenBy['code'], $noExtraBlanksTokens, true))) {
            $error = 'Immediately nested control blocks should not be preceded by blank lines (' . max(0, $newLines - 1) .
                 ' found)';
            $phpcsFile->addError($error, $ptr + 2, 'ExtraBlankLines');
        }
    }

    private function getBlankLineCountAfter(\PHP_CodeSniffer_File $phpcsFile, $tokens, $startPtr, $endPtr,
        array $extraTokens = array())
    {
        $it = new TokenIterator($tokens, $startPtr, $endPtr);
        $brokenBy = null;
        $newLines = 0;

        foreach ($it as $ptr => $token) {
            if ($token['code'] == T_WHITESPACE) {
                $content = str_replace(' ', '', $token['content']);
                $newLines += substr_count($content, PHP_EOL);
            }
            else {
                $brokenBy = $token;

                break;
            }
        }

        $noExtraBlanksTokens = array_merge([
            T_CLOSE_CURLY_BRACKET
        ], $extraTokens);

        if ($newLines != 2 && $brokenBy && ! in_array($brokenBy['code'], $noExtraBlanksTokens, true)) {
            $error = 'Control blocks should be followed by exactly one blank line (' . max(0, $newLines - 1) . ' found)';
            $phpcsFile->addError($error, $ptr, 'NotExactlyOneBlankLine');
        }
        elseif ($newLines > 1 && (! $brokenBy || in_array($brokenBy['code'], $noExtraBlanksTokens, true))) {
            $error = 'There must not be blank lines between consecutive closing brackets or between if/elseif/else blocks';
            $phpcsFile->addError($error, $ptr - 2, 'ExtraBlankLines');
        }
    }
}

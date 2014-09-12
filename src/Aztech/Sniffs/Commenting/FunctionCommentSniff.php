<?php

namespace Aztech\Sniffs\Commenting;

/**
 * Sniff to detect functions that are not preceded by a comment
 * @author thibaud
 *
 */
class FunctionCommentSniff implements \PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(T_FUNCTION);
    }

    public function process(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $types = [
            T_SEMICOLON,
            T_CLOSE_CURLY_BRACKET,
            T_CURLY_OPEN,
            T_OPEN_CURLY_BRACKET
        ];

        $previousPtr = $phpcsFile->findPrevious($types, $stackPtr);
        $startCommentPtr = $phpcsFile->findNext([ T_DOC_COMMENT ], $previousPtr, $stackPtr);

        if (! $startCommentPtr) {
            $error = sprintf('There must a DocBlock comment for the function', $phpcsFile->getDeclarationName($stackPtr));
            $phpcsFile->addError($error, $stackPtr, 'NoDocBlock');

            return;
        }

        $endCommentPtr = $phpcsFile->findNext(T_DOC_COMMENT, $startCommentPtr, $stackPtr, true);
        $token = $phpcsFile->getTokens($startCommentPtr - 1, $endCommentPtr - $startCommentPtr + 1);
    }
}

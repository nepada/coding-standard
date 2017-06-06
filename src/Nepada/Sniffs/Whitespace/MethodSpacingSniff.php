<?php
declare(strict_types = 1);

/**
 * This file is part of the nepada/coding-standard.
 * Copyright (c) 2016 Petr Morávek (petr@pada.cz)
 */

namespace Nepada\Sniffs\Whitespace;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Standards_AbstractScopeSniff;


/**
 * Ensure spacing between functions/methods and add extra spacing between class attributes and methods.
 * Based on Squiz_Sniffs_WhiteSpace_FunctionSpacingSniff by Greg Sherwood <gsherwood@squiz.net> and Marc McIntyre <mmcintyre@squiz.net>.
 */
class MethodSpacingSniff extends PHP_CodeSniffer_Standards_AbstractScopeSniff
{

    /** @var int */
    public $regularSpacing = 1;

    /** @var int */
    public $extraSpacing = 1;


    /**
     * Constructs a Squiz_Sniffs_Scope_MethodScopeSniff.
     */
    public function __construct()
    {
        parent::__construct([T_CLASS, T_TRAIT, T_INTERFACE], [T_FUNCTION]);
    }

    /**
     * TODO: add scalar typehints
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     * @param int $scopePointer The current scope opener token.
     */
    protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $pointer, $scopePointer)
    {
        $this->checkSpacingBeforeFunction($phpcsFile, $pointer, $scopePointer);
        $this->checkSpacingAfterFunction($phpcsFile, $pointer);
    }

    /**
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     * @param int $scopePointer The current scope opener token.
     */
    private function checkSpacingBeforeFunction(PHP_CodeSniffer_File $phpcsFile, int $pointer, int $scopePointer)
    {
        $tokens = $phpcsFile->getTokens();
        $expectedSpacing = $this->regularSpacing;

        $prevLineToken = $pointer - 1;
        while ($prevLineToken > 0 && $tokens[$prevLineToken]['line'] === $tokens[$pointer]['line']) {
            $prevLineToken--;
        }

        if ($tokens[$prevLineToken]['line'] === $tokens[$pointer]['line']) {
            // Never found the previous line, which means
            // there are 0 blank lines before the function.
            $foundLines = 0;

        } else {
            $prevContent = $phpcsFile->findPrevious(T_WHITESPACE, $prevLineToken, null, true);
            if ($tokens[$prevContent]['code'] === T_DOC_COMMENT_CLOSE_TAG && $tokens[$prevContent]['line'] === ($tokens[$pointer]['line'] - 1)) {
                // Account for function comments.
                $prevContent = $phpcsFile->findPrevious(T_WHITESPACE, ($tokens[$prevContent]['comment_opener'] - 1), null, true);
            }

            // Before we throw an error, check that we are not throwing an error
            // for another function. We don't want to error for no blank lines after
            // the previous function and no blank lines before this one as well.
            $i = $pointer - 1;
            $foundLines = 0;
            while ($tokens[$i]['line'] >= $tokens[$prevContent]['line'] && $i > 0) {
                if (isset($tokens[$i]['scope_condition']) === true) {
                    $scopeCondition = $tokens[$i]['scope_condition'];
                    if ($tokens[$scopeCondition]['code'] === T_FUNCTION) {
                        // Found a previous function.
                        return;
                    }
                } elseif ($tokens[$i]['code'] === T_FUNCTION) {
                    // Found another interface function.
                    return;
                } elseif ($tokens[$i]['code'] === T_NAMESPACE) {
                    // Namespace declaration... should be handled elsewhere
                    return;
                } elseif ($tokens[$i]['code'] === T_USE && UseDeclarationSpacingSniff::isImportNamespaceUse($phpcsFile, $i)) {
                    // Use import declaration... this should be handled elsewhere
                    return;
                }

                if (
                    $tokens[$i]['code'] === T_WHITESPACE
                    && $tokens[($i - 1)]['line'] < $tokens[$i]['line']
                    && $tokens[($i + 1)]['line'] > $tokens[$i]['line']
                ) {
                    // This token is on a line by itself. If it is whitespace, the line is empty.
                    $foundLines++;
                }

                $i--;
            }

            for ($i = $tokens[$scopePointer]['scope_opener']; $i <= $prevContent; $i++) {
                if ($tokens[$i]['code'] === T_FUNCTION) {
                    $expectedSpacing = $this->regularSpacing;
                    break;
                } elseif (in_array($tokens[$i]['code'], [T_CONST, T_VARIABLE, T_USE], true)) {
                    $expectedSpacing = $this->regularSpacing + $this->extraSpacing;
                }
            }
        }

        if ($foundLines !== $expectedSpacing) {
            $error = 'Expected %s blank line';
            if ($expectedSpacing !== 1) {
                $error .= 's';
            }

            $error .= ' before function; %s found';
            $data = [$expectedSpacing, $foundLines];

            $phpcsFile->addError($error, $pointer, 'Before', $data);
        }
    }

    /**
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     */
    private function checkSpacingAfterFunction(PHP_CodeSniffer_File $phpcsFile, int $pointer)
    {
        $tokens = $phpcsFile->getTokens();
        $expectedSpacing = $this->regularSpacing;

        if (!isset($tokens[$pointer]['scope_closer'])) {
            // Must be an interface method, so the closer is the semicolon.
            $closer = $phpcsFile->findNext([T_SEMICOLON], $pointer);
        } else {
            $closer = $tokens[$pointer]['scope_closer'];
        }

        // Allow for comments on the same line as the closer.
        $nextLineToken = $closer + 1;
        while ($nextLineToken < $phpcsFile->numTokens && $tokens[$nextLineToken]['line'] === $tokens[$closer]['line']) {
            $nextLineToken++;
        }

        if ($nextLineToken === $phpcsFile->numTokens - 1) {
            // We are at the end of the file.
            // Don't check spacing after the function because this
            // should be done by an EOF sniff.
            return;
        }

        $nextContent = $phpcsFile->findNext([T_WHITESPACE], $nextLineToken, null, true);
        if ($nextContent === false) {
            // We are at the end of the file.
            // Don't check spacing after the function because this
            // should be done by an EOF sniff.
            return;
        }

        $foundLines = $tokens[$nextContent]['line'] - $tokens[$nextLineToken]['line'];

        if ($foundLines !== $expectedSpacing) {
            $error = 'Expected %s blank line';
            if ($expectedSpacing !== 1) {
                $error .= 's';
            }

            $error .= ' after function; %s found';
            $data = [$expectedSpacing, $foundLines];

            $phpcsFile->addError($error, $closer, 'After', $data);
        }
    }

}

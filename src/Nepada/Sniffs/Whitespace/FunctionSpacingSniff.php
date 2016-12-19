<?php
/**
 * This file is part of the nepada/coding-standard.
 * Copyright (c) 2016 Petr Morávek (petr@pada.cz)
 */

namespace Nepada\Sniffs\Whitespace;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;


/**
 * Ensure spacing between functions/methods and add extra spacing between class attributes and methods.
 * Based on Squiz_Sniffs_WhiteSpace_FunctionSpacingSniff by Greg Sherwood <gsherwood@squiz.net> and Marc McIntyre <mmcintyre@squiz.net>.
 */
class FunctionSpacingSniff implements PHP_CodeSniffer_Sniff
{

    /** @var int */
    public $regularSpacing = 1;

    /** @var int */
    public $extraSpacing = 1;


    /**
     * @return integer[]
     */
    public function register()
    {
        return [
            T_FUNCTION,
        ];

    }

    /**
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $pointer)
    {
        $this->regularSpacing = (int) $this->regularSpacing;

        $this->checkSpacingBeforeFunction($phpcsFile, $pointer);
        $this->checkSpacingAfterFunction($phpcsFile, $pointer);
    }

    /**
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     */
    private function checkSpacingBeforeFunction(PHP_CodeSniffer_File $phpcsFile, $pointer)
    {
        $tokens = $phpcsFile->getTokens();
        $expectedSpacing = (int) $this->regularSpacing;

        $prevLineToken = $pointer - 1;
        while ($prevLineToken > 0 && $tokens[$prevLineToken]['line'] === $tokens[$pointer]['line']) {
            $prevLineToken--;
        }

        if ($tokens[$prevLineToken]['line'] === $tokens[$pointer]['line']) {
            // Never found the previous line, which means
            // there are 0 blank lines before the function.
            $foundLines = 0;

        } else {
            $currentLine = $tokens[$pointer]['line'];

            $prevContent = $phpcsFile->findPrevious(T_WHITESPACE, $prevLineToken, null, true);
            if ($tokens[$prevContent]['code'] === T_DOC_COMMENT_CLOSE_TAG && $tokens[$prevContent]['line'] === ($currentLine - 1)) {
                // Account for function comments.
                $prevContent = $phpcsFile->findPrevious(T_WHITESPACE, ($tokens[$prevContent]['comment_opener'] - 1), null, true);
            }

            // Before we throw an error, check that we are not throwing an error
            // for another function. We don't want to error for no blank lines after
            // the previous function and no blank lines before this one as well.
            $prevLine = $tokens[$prevContent]['line'] - 1;
            $i = $pointer - 1;
            $foundLines = 0;
            while ($currentLine !== $prevLine && $currentLine > 1 && $i > 0) {
                if (isset($tokens[$i]['scope_condition']) === true) {
                    $scopeCondition = $tokens[$i]['scope_condition'];
                    if ($tokens[$scopeCondition]['code'] === T_FUNCTION) {
                        // Found a previous function.
                        return;
                    }
                } elseif ($tokens[$i]['code'] === T_FUNCTION) {
                    // Found another interface function.
                    return;
                } elseif (
                    $tokens[$i]['code'] === T_NAMESPACE
                    || ($tokens[$i]['code'] === T_USE && UseDeclarationSpacingSniff::isImportNamespaceUse($phpcsFile, $i))
                ) {
                    // Namespace or import declaration... this should be handled elsewhere
                    return;
                } elseif (in_array($tokens[$i]['code'], [T_CONST, T_VARIABLE, T_USE], true)) {
                    $expectedSpacing = $this->regularSpacing + $this->extraSpacing;
                }

                $currentLine = $tokens[$i]['line'];
                if ($currentLine === $prevLine) {
                    break;
                }

                if ($tokens[($i - 1)]['line'] < $currentLine && $tokens[($i + 1)]['line'] > $currentLine) {
                    // This token is on a line by itself. If it is whitespace, the line is empty.
                    if ($tokens[$i]['code'] === T_WHITESPACE) {
                        $foundLines++;
                    }
                }

                $i--;
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
    private function checkSpacingAfterFunction(PHP_CodeSniffer_File $phpcsFile, $pointer)
    {
        $tokens = $phpcsFile->getTokens();
        $expectedSpacing = $this->regularSpacing;

        if (!isset($tokens[$pointer]['scope_closer'])) {
            // Must be an interface method, so the closer is the semicolon.
            $closer = $phpcsFile->findNext(T_SEMICOLON, $pointer);
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

        $nextContent = $phpcsFile->findNext(T_WHITESPACE, $nextLineToken, null, true);
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

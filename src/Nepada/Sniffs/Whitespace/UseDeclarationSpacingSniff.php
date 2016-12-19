<?php
/**
 * This file is part of the nepada/coding-standard.
 * Copyright (c) 2016 Petr Morávek (petr@pada.cz)
 */

namespace Nepada\Sniffs\Whitespace;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;


/**
 * Ensure spacing between import statements and rest of the code.
 */
class UseDeclarationSpacingSniff implements PHP_CodeSniffer_Sniff
{

    /** @var int */
    public $otherSpacing = 2;


    /**
     * @return integer[]
     */
    public function register()
    {
        return [
            T_USE,
        ];

    }

    /**
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $pointer)
    {
        if ($this->shouldIgnore($phpcsFile, $pointer)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $nextUse = $phpcsFile->findNext(T_USE, $pointer + 1);
        while ($nextUse && $this->shouldIgnore($phpcsFile, $nextUse)) {
            $nextUse = $phpcsFile->findNext(T_USE, $nextUse + 1);
        }

        if ($nextUse !== false) {
            return;
        }

        $endOfUse = $phpcsFile->findNext(T_SEMICOLON, $pointer + 1);
        $nextContent = $phpcsFile->findNext(T_WHITESPACE, $endOfUse + 1, null, true);

        if ($tokens[$nextContent]['code'] === T_CLOSE_TAG) {
            return;
        }

        $expectedSpacing = (int) $this->otherSpacing;
        $foundLines = $tokens[$nextContent]['line'] - $tokens[$endOfUse]['line'] - 1;
        if ($foundLines !== $expectedSpacing) {
            $error = 'Expected %s blank line';
            if ($expectedSpacing !== 1) {
                $error .= 's';
            }

            $error .= ' after the last use statement; %s found';
            $data = [$expectedSpacing, $foundLines];

            $phpcsFile->addError($error, $pointer, '', $data);
        }

    }

    /**
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     * @return bool
     */
    private function shouldIgnore(PHP_CodeSniffer_File $phpcsFile, $pointer)
    {
        $tokens = $phpcsFile->getTokens();

        // Ignore USE keywords inside closures.
        $next = $phpcsFile->findNext(T_WHITESPACE, $pointer + 1, null, true);
        if ($tokens[$next]['code'] === T_OPEN_PARENTHESIS) {
            return true;
        }

        // Ignore USE keywords for traits.
        if ($phpcsFile->hasCondition($pointer, [T_CLASS, T_TRAIT])) {
            return true;
        }

        return false;
    }

}

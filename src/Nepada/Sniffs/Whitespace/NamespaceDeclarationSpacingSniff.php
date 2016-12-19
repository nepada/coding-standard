<?php
/**
 * This file is part of the nepada/coding-standard.
 * Copyright (c) 2016 Petr Morávek (petr@pada.cz)
 */

namespace Nepada\Sniffs\Whitespace;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;


/**
 * Ensure spacing between namespace declaration and import statements/rest of the code.
 */
class NamespaceDeclarationSpacingSniff implements PHP_CodeSniffer_Sniff
{

    /** @var int */
    public $useSpacing = 1;

    /** @var int */
    public $otherSpacing = 2;


    /**
     * @return integer[]
     */
    public function register()
    {
        return [
            T_NAMESPACE,
        ];

    }

    /**
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $pointer)
    {
        $tokens = $phpcsFile->getTokens();

        $nextLineToken = $pointer + 1;
        while ($nextLineToken < ($phpcsFile->numTokens - 1) && $tokens[$nextLineToken]['line'] === $tokens[$pointer]['line']) {
            $nextLineToken++;
        }

        $nextContent = $phpcsFile->findNext(T_WHITESPACE, $nextLineToken, $phpcsFile->numTokens, true);
        if ($nextContent === false) {
            return;
        }

        $isUseStatement = false;
        $nextContentLine = $tokens[$nextContent]['line'];
        $i = $nextContent;
        while ($i < ($phpcsFile->numTokens - 1) && $tokens[$i]['line'] === $nextContentLine) {
            if ($tokens[$i]['code'] === T_USE) {
                $isUseStatement = true;
                break;
            }
            $i++;
        }

        $expectedSpacing = (int) ($isUseStatement ? $this->useSpacing : $this->otherSpacing);
        $foundLines = $tokens[$nextContent]['line'] - $tokens[$nextLineToken]['line'];

        if ($foundLines !== $expectedSpacing) {
            $error = 'Expected %s blank line';
            if ($expectedSpacing !== 1) {
                $error .= 's';
            }

            $error .= ' after namespace declaration; %s found';
            $data = [$expectedSpacing, $foundLines];

            $phpcsFile->addError($error, $pointer, $isUseStatement ? 'Use' : 'Other', $data);
        }
    }

}

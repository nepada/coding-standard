<?php
/**
 * This file is part of the nepada/coding-standard.
 * Copyright (c) 2016 Petr Morávek (petr@pada.cz)
 */

declare(strict_types = 1);

namespace Nepada\Sniffs\Whitespace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;


/**
 * Ensure spacing between namespace declaration and import statements/rest of the code.
 */
class NamespaceDeclarationSpacingSniff implements Sniff
{

    /** @var int */
    public $useSpacing = 1;

    /** @var int */
    public $otherSpacing = 2;


    /**
     * @return int[]
     */
    public function register(): array
    {
        return [
            T_NAMESPACE,
        ];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
     * @param File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     * @return void
     */
    public function process(File $phpcsFile, $pointer)
    {
        $tokens = $phpcsFile->getTokens();

        $nextLineToken = $pointer + 1;
        while ($nextLineToken < $phpcsFile->numTokens && $tokens[$nextLineToken]['line'] === $tokens[$pointer]['line']) {
            $nextLineToken++;
        }

        $nextContent = $phpcsFile->findNext([T_WHITESPACE], $nextLineToken, $phpcsFile->numTokens, true);
        if ($nextContent === false) {
            return;
        }

        $isUseStatement = false;
        $nextContentLine = $tokens[$nextContent]['line'];
        $i = $nextContent;
        while ($i < $phpcsFile->numTokens && $tokens[$i]['line'] === $nextContentLine) {
            if ($tokens[$i]['code'] === T_USE) {
                $isUseStatement = true;
                break;
            }
            $i++;
        }

        $expectedSpacing = $isUseStatement ? $this->useSpacing : $this->otherSpacing;
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

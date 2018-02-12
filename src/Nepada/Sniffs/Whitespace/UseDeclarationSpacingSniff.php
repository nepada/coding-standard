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
 * Ensure spacing between import statements and rest of the code.
 */
class UseDeclarationSpacingSniff implements Sniff
{

    /** @var int */
    public $otherSpacing = 2;


    /**
     * @return int[]
     */
    public function register(): array
    {
        return [
            T_USE,
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
        if (!static::isImportNamespaceUse($phpcsFile, $pointer)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $nextUse = $phpcsFile->findNext([T_USE], $pointer + 1);
        while (is_int($nextUse) && !static::isImportNamespaceUse($phpcsFile, $nextUse)) {
            $nextUse = $phpcsFile->findNext([T_USE], $nextUse + 1);
        }

        if ($nextUse !== false) {
            return;
        }

        $endOfUse = $phpcsFile->findNext([T_SEMICOLON], $pointer + 1);
        $nextContent = $phpcsFile->findNext([T_WHITESPACE], $endOfUse + 1, null, true);

        if ($tokens[$nextContent]['code'] === T_CLOSE_TAG) {
            return;
        }

        $expectedSpacing = $this->otherSpacing;
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
     * @param File $phpcsFile The file being scanned.
     * @param int $pointer The position of the current token in the stack passed in $tokens.
     * @return bool
     */
    public static function isImportNamespaceUse(File $phpcsFile, int $pointer): bool
    {
        $tokens = $phpcsFile->getTokens();

        // Ignore USE keywords inside closures.
        $next = $phpcsFile->findNext([T_WHITESPACE], $pointer + 1, null, true);
        if ($tokens[$next]['code'] === T_OPEN_PARENTHESIS) {
            return false;
        }

        // Ignore USE keywords for traits.
        if ($phpcsFile->hasCondition($pointer, [T_CLASS, T_TRAIT])) {
            return false;
        }

        return true;
    }

}

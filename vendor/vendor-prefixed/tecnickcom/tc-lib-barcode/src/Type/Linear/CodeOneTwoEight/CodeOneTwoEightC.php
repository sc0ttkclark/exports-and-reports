<?php

declare(strict_types=1);

/**
 * CodeOneTwoEightC.php
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2010-2026 Nicola Asuni - Tecnick.com LTD
 * @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 *
 * This file is part of tc-lib-barcode software library.
 */

namespace ExportsReports\Prefixed\Com\Tecnick\Barcode\Type\Linear\CodeOneTwoEight;

use ExportsReports\Prefixed\Com\Tecnick\Barcode\Exception as BarcodeException;

/**
 * ExportsReports\Prefixed\Com\Tecnick\Barcode\Type\Linear\CodeOneTwoEight\CodeOneTwoEightC;
 *
 * CodeOneTwoEightC Barcode type class
 * CODE 128 C
 *
 * @since       2015-02-21
 * @category    Library
 * @package     Barcode
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2010-2026 Nicola Asuni - Tecnick.com LTD
 * @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE)
 * @link        https://github.com/tecnickcom/tc-lib-barcode
 */
class CodeOneTwoEightC extends \ExportsReports\Prefixed\Com\Tecnick\Barcode\Type\Linear\CodeOneTwoEight
{
    /**
     * Barcode format
     *
     * @var string
     */
    protected const FORMAT = 'C128C';

    /**
     * Get the code point array
     *
     * @return array<int, int>
     *
     * @throws BarcodeException in case of error
     */
    protected function getCodeData(): array
    {
        $code = $this->code;
        $code_data = [];
        if ($code !== '' && \ord($code[0]) === 241) {
            $code_data[] = 102;
            $code = \substr($code, 1);
        }

        $this->getCodeDataC($code_data, $code);
        return $this->finalizeCodeData($code_data, 105);
    }
}

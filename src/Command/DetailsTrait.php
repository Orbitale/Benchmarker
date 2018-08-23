<?php

declare(strict_types=1);

/**
 * This file is part of the benchmarker package.
 *
 * (c) Alexandre Rock Ancelet <alex@orbitale.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orbitale\Benchmarker\Command;

use Symfony\Component\Console\Style\StyleInterface;

trait DetailsTrait
{
    protected function outputDetails(StyleInterface $io, $count)
    {
        $io->table(
            ['Orbitale benchmarking tool'],
            [
                ['Start', date('Y-m-d H:i:s')],
                ['PHP version', PHP_VERSION],
                ['Platform', PHP_OS],
                ['Each test is executed ', $count.' times'],
            ]
        );
    }
}

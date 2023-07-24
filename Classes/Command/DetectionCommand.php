<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\Pagebased\Utility\DetectionUtility;
use Zeroseven\Pagebased\Utility\RootLineUtility;

class DetectionCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('startingPoint', InputArgument::REQUIRED, 'Define the starting point in TYPO3\'s page tree.');
        $this->addArgument('depth', InputArgument::OPTIONAL, 'Specify how deep to search from the starting point.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startingPoint = (int)$input->getArgument('startingPoint');
        $depth = MathUtility::canBeInterpretedAsInteger($value = $input->getArgument('depth')) ? (int)$value : null;
        $rootLine = RootLineUtility::collectPagesBelow($startingPoint, false, $depth);

        foreach ($rootLine as $uid => $page) {
            DetectionUtility::updateFields($uid);
        }

        return 0;
    }
}

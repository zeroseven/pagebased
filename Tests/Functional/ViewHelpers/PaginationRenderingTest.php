<?php

declare(strict_types=1);

namespace Zeroseven\Pagebased\Tests\Functional\ViewHelpers;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional rendering test for the pagination ViewHelpers.
 *
 * TYPO3 v14 ships Fluid v5, which removed the static renderStatic() /
 * CompileWithRenderStatic rendering path. PaginationViewHelper,
 * Pagination/EachStageViewHelper and Pagination/EachItemViewHelper were migrated
 * to the instance method render(). This test renders a template that nests all
 * three and asserts they still produce output, proving the migration.
 */
class PaginationRenderingTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pagebased',
    ];

    protected array $coreExtensionsToLoad = [
        'core',
        'frontend',
        'fluid',
    ];

    /** @param string[] $labels */
    private function render(array $labels): string
    {
        // Pagination expects objects (it attaches them to an ObjectStorage).
        $items = array_map(static function (string $label): \stdClass {
            $item = new \stdClass();
            $item->label = $label;

            return $item;
        }, $labels);

        $viewFactory = $this->get(ViewFactoryInterface::class);
        $view = $viewFactory->create(new ViewFactoryData(
            templatePathAndFilename: __DIR__ . '/../Fixtures/Templates/Pagination.html',
            request: new ServerRequest(),
        ));
        $view->assign('items', $items);

        return trim((string)$view->render());
    }

    public function testPaginationViewHelpersRenderAllItemsAcrossStages(): void
    {
        $output = $this->render(['A', 'B', 'C', 'D']);

        foreach (['[A]', '[B]', '[C]', '[D]'] as $expected) {
            self::assertStringContainsString($expected, $output, sprintf('Rendered pagination output should contain "%s". Got: %s', $expected, $output));
        }
    }

    public function testPaginationViewHelpersRenderSingleStage(): void
    {
        // Fewer items than itemsPerStage → a single stage with a single item.
        $output = $this->render(['solo']);

        self::assertStringContainsString('[solo]', $output);
    }
}

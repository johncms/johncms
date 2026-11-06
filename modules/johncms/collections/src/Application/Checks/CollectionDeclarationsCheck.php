<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Checks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;

final readonly class CollectionDeclarationsCheck implements SystemCheckInterface
{
    public function __construct(private CollectionDeclarationsSynchronizer $synchronizer)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO(id: 'collections.declarations', name: d__('collections', 'Collection declarations'), group: d__('collections', 'Collections'), icon: 'list-tree', order: 80);
    }

    public function run(): array
    {
        $problems = $this->synchronizer->problems();
        return [new CheckResultDTO(
            'collections.declarations',
            self::definition()->name,
            (string) count($problems),
            $problems === [] ? CheckStatus::Passed : CheckStatus::Failed,
            CheckSeverity::Critical,
            $problems === [] ? d__('collections', 'Collection declarations match the database.') : implode("\n", $problems)
        )];
    }
}
